<?php
/**
 * Reverb
 * ReverbBroadcastHookHandler
 * Includes MIT licensed code from Extension:Echo.
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

declare( strict_types=1 );

namespace Reverb;

use Config;
use Fandom\Includes\User\UserInfo;
use Fandom\Includes\Util\UrlUtilityService;
use FlaggedRevsRevisionReviewFormAfterDoSubmitHook;
use MediaWiki\Deferred\LinksUpdate\TitleLinksTable;
use MediaWiki\Hook\AbortEmailNotificationHook;
use MediaWiki\Hook\EmailUserCompleteHook;
use MediaWiki\Hook\LinksUpdateHook;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\User\Hook\UserGroupsChangedHook;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserOptionsLookup;
use MWTimestamp;
use NamespaceInfo;
use RecentChange;
use RequestContext;
use Reverb\Notification\NotificationBroadcastFactory;
use SpecialPage;
use Title;
use User;
use UserArray;
use UserArrayFromResult;
use WatchedItemStore;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\ILoadBalancer;
use WikiPage;

class ReverbBroadcastHookHandler implements
	PageSaveCompleteHook,
	UserGroupsChangedHook,
	LinksUpdateHook,
	FlaggedRevsRevisionReviewFormAfterDoSubmitHook,
	AbortEmailNotificationHook,
	EmailUserCompleteHook
{
	public function __construct(
		private Config $config,
		private UserFactory $userFactory,
		private RevisionLookup $revisionLookup,
		private NamespaceInfo $namespaceInfo,
		private RevisionStore $revisionStore,
		private UserOptionsLookup $userOptionsLookup,
		private LanguageFactory $languageFactory,
		private ILoadBalancer $loadBalancer,
		private WatchedItemStore $watchedItemStore,
		private UserInfo $userInfo,
		private UrlUtilityService $urlUtilityService,
		private NotificationBroadcastFactory $notificationBroadcastFactory
	) {
	}

	private function onPageContentSaveComplete(
		WikiPage $wikiPage,
		UserIdentity $user,
		string $summary,
		RevisionRecord $revisionRecord,
		EditResult $editResult
	): void {
		if ( $editResult->isNullEdit() ) {
			return;
		}

		$title = $wikiPage->getTitle();
		$agent = $this->userFactory->newFromUserIdentity( $user );
		if ( $title->getNamespace() == NS_USER_TALK ) {
			$notifyUser = $this->userFactory->newFromName( $title->getText() );
			// If the recipient is a valid non-anonymous user and hasn't turned off their
			// notifications, generate a talk page post Echo notification.
			if ( $notifyUser && $notifyUser->getId() && !$notifyUser->equals( $user ) ) {
				// If this is a minor edit, only notify if the agent doesn't have talk page
				// minor edit notification blocked.
				if ( !$revisionRecord->isMinor() || !$agent->isAllowed( 'nominornewtalk' ) ) {
					$notifyUserTalk = Title::newFromText( $notifyUser->getName(), NS_USER_TALK );
					$broadcast = $this->notificationBroadcastFactory->new(
						'user-interest-talk-page-edit',
						$agent,
						$notifyUser,
						[
							'url' => $this->getUserFacingUrl( $title ),
							'message' => [
								[ 'user_note', htmlentities( $summary, ENT_QUOTES ) ],
								[ 1, $this->getAgentPageUrl( $agent ) ],
								[ 2, $agent->getName() ],
								[ 3, $this->getUserFacingUrl( $notifyUserTalk ) ],
								[ 4, $agent->getName() ],
							],
						]
					);
					if ( $broadcast ) {
						$broadcast->transmit();
					}
				}
			}
		}

		// Reverted edits $undidRevId.
		$undidRevId = $editResult->getNewestRevertedRevisionId();
		if ( $undidRevId > 0 ) {
			$undidRevision = $this->revisionLookup->getRevisionById( $undidRevId );
			if ( $undidRevision && $undidRevision->getPage()->getId() === $wikiPage->getId() ) {
				$notifyUser = $this->userFactory->newFromUserIdentity( $undidRevision->getUser() );
				if ( $notifyUser && $notifyUser->getId() && !$notifyUser->equals( $agent ) ) {
					$broadcast = $this->notificationBroadcastFactory->new(
						'article-edit-revert',
						$agent,
						$notifyUser,
						[
							'url' => $this->getUserFacingUrl( $title ),
							'message' => [
								[ 'user_note', htmlentities( $summary, ENT_QUOTES ) ],
								[ 1, $agent->getName() ],
								[ 2, $title->getFullText() ],
								[ 3, 1 ],
								[ 4, $this->getUserFacingUrl( $title ) ],
								[
									5,
									$this->getUserFacingUrl( $title, [
										'type' => 'revision',
										'oldid' => $undidRevId,
										'diff' => $revisionRecord->getId(),
									] ),
								],
							],
						]
					);
					if ( $broadcast ) {
						$broadcast->transmit();
					}
				}
			}
		}
	}

	/** @inheritDoc */
	public function onUserGroupsChanged( $user, $added, $removed, $performer, $reason, $oldUGMs, $newUGMs ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ||
			// TODO: Implement support for autopromotion
			!$performer ||
			// TODO: Support UserRightsProxy
			!$user instanceof User ||
			// Don't notify for self changes.
			$user->equals( $performer )
		) {
			return;
		}

		// If any old groups are in $add, those groups are having their expiry
		// changed, not actually being added
		$expiryChanged = [];
		$reallyAdded = [];
		foreach ( $added as $group ) {
			if ( isset( $oldUGMs[ $group ] ) ) {
				$expiryChanged[] = $group;
			} else {
				$reallyAdded[] = $group;
			}
		}

		$url = $this->getUserFacingUrl( Title::newFromText( $user->getName(), NS_USER ) );
		if ( $expiryChanged ) {
			// @TODO: Fix user note.
			$broadcast = $this->notificationBroadcastFactory->newSingle(
				'user-account-groups-expiration-change',
				$performer,
				$user,
				[
					'url' => $url,
					'message' => [
						[ 'user_note', '' ],
						[ 1, $user->getName() ],
						[ 2, implode( ', ', $expiryChanged ) ],
						[ 3, count( $expiryChanged ) ],
					],
				]
			);
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}

		if ( $reallyAdded || $removed ) {
			$broadcast = $this->notificationBroadcastFactory->newSingle(
				'user-account-groups-changed',
				$performer,
				$user,
				[
					'url' => $url,
					'message' => [
						[
							'user_note',
							( count( $reallyAdded ) ?
								wfMessage(
									'user-note-user-account-groups-changed-added',
									implode( ', ', $reallyAdded )
								)->parse() .
								( count( $removed ) ? "\n" : '' ) : '' ) .
							( count( $removed ) ?
								wfMessage(
									'user-note-user-account-groups-changed-removed',
								implode( ', ', $removed )
								)->parse() : '' ),
						],
						[ 1, $user->getName() ],
					],
				]
			);
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}
	}

	/** @inheritDoc */
	public function onLinksUpdate( $linksUpdate ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ) {
			return;
		}

		// @FIXME: This doesn't work in 1.27+
		// Rollback or undo should not trigger link notification
		// @TODO: Implement a better solution so it doesn't depend on the checking of
		// a specific set of request variables
		$request = RequestContext::getMain()->getRequest();
		if ( $request->getVal( 'wpUndidRevision' ) || $request->getVal( 'action' ) === 'rollback' ) {
			return;
		}

		// Handle only
		// 1. inserts to pagelinks table &&
		// 2. content namespace pages &&
		// 3. non-transcluding pages &&
		// 4. non-redirect pages
		if ( !$this->namespaceInfo->isContent( $linksUpdate->getTitle()->getNamespace() ) ||
			!$linksUpdate->isRecursive() ||
			$linksUpdate->getTitle()->isRedirect()
		) {
			return;
		}

		$insertedLinks = $linksUpdate->getPageReferenceArray( 'pagelinks', TitleLinksTable::INSERTED );
		if ( count( $insertedLinks ) === 0 ) {
			return;
		}

		$agent = $this->userFactory->newFromUserIdentity( $linksUpdate->getTriggeringUser() );

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		foreach ( $insertedLinks as $page ) {
			if ( !$this->namespaceInfo->isContent( $page->getNamespace() ) ) {
				continue;
			}

			$linkToTitle = Title::castFromPageReference( $page );
			Assert::precondition( $linkToTitle !== null, 'Title casted from PageReference cannot be null' );
			if ( $linkToTitle->isRedirect() ) {
				continue;
			}

			$userIds = $db->selectFieldValues(
				'watchlist',
				'wl_user',
				[
					'wl_user != ' . $agent->getId(),
					'wl_namespace' => $page->getNamespace(),
					'wl_title' => $page->getDBkey(),
				],
				__METHOD__
			);

			$targets = UserArrayFromResult::newFromIDs( $userIds );
			$notifyUsers = [];
			foreach ( $targets as $target ) {
				$notifyUsers[] = $target;
			}

			$broadcast = $this->notificationBroadcastFactory->new(
				'article-edit-page-linked',
				$agent,
				$notifyUsers,
				[
					'url' => $this->getUserFacingUrl( $linkToTitle ),
					'message' => [
						[ 'user_note', '' ],
						[ 1, $linksUpdate->getTitle()->getFullText() ],
						[ 2, $linkToTitle->getFullText() ],
						[ 3, $this->getUserFacingUrl( $linksUpdate->getTitle() ) ],
						[ 4, $this->getUserFacingUrl( $linkToTitle ) ],
						[ 5, $this->getAgentPageUrl( $agent ) ],
						[ 6, $agent->getName() ],
					],
				]
			);
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}
	}

	private function onArticleRollbackComplete( WikiPage $wikiPage, UserIdentity $user, EditResult $editResult ): void {
		$oldRevision = $this->revisionLookup->getRevisionById( $editResult->getNewestRevertedRevisionId() );
		$newRevision = $this->revisionLookup->getRevisionById( $editResult->getOriginalRevisionId() );
		$notifyUser = $oldRevision->getUser();

		// Skip anonymous users and null edits.
		if ( !$notifyUser ||
			!$notifyUser->isRegistered() ||
			$notifyUser->equals( $user ) ||
			$oldRevision->getContent( SlotRecord::MAIN )->equals( $newRevision->getContent( SlotRecord::MAIN ) )
		) {
			return;
		}

		// @TODO: Fix user note and count reverted revisions. Echo defaulted to plural/2 for rollback.
		$title = $wikiPage->getTitle();
		$broadcast = $this->notificationBroadcastFactory->newSingle(
			'article-edit-revert',
			$this->userFactory->newFromUserIdentity( $user ),
			$this->userFactory->newFromUserIdentity( $notifyUser ),
			[
				'url' => $this->getUserFacingUrl( $title ),
				'message' => [
					[ 'user_note', '' ],
					[ 1, $notifyUser->getName() ],
					[ 2, $title->getFullText() ],
					[ 3, 2 ],
					[ 4, $this->getUserFacingUrl( $title ) ],
					[
						5,
						$this->getUserFacingUrl( $title, [
							'type' => 'revision',
							'oldid' => $oldRevision->getId(),
							'diff' => $wikiPage->getRevisionRecord()->getId(),
						] ),
					],
				],
			] );
		if ( $broadcast ) {
			$broadcast->transmit();
		}
	}

	/** @inheritDoc */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ) {
			return;
		}

		if ( $editResult->isRevert() ) {
			$this->onArticleRollbackComplete( $wikiPage, $user, $editResult );
		} else {
			$this->onPageContentSaveComplete( $wikiPage, $user, $summary, $revisionRecord, $editResult );
		}
	}

	/** @inheritDoc */
	public function onFlaggedRevsRevisionReviewFormAfterDoSubmit( $form, $status ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ||
			$form->getAction() !== 'reject' ||
			$status === false
		) {
			return;
		}

		// revid -> userid
		$affectedRevisions = [];
		$revQuery = $this->revisionStore->getQueryInfo();
		$newRev = $this->revisionLookup->getRevisionByTitle( $form->getPage(), $form->getOldId() );
		$oldRev = $this->revisionLookup->getRevisionByTitle( $form->getPage(), $form->getRefId() );

		$revisions = $this->loadBalancer->getConnection( DB_REPLICA )
			->select(
				$revQuery[ 'tables' ],
				[ 'rev_id', 'rev_user' => $revQuery[ 'fields' ][ 'rev_user' ] ],
				[
					'rev_id <= ' . $newRev->getId(),
					"rev_timestamp <= " . $newRev->getTimestamp(),
					'rev_id > ' . $oldRev->getId(),
					"rev_timestamp > " . $oldRev->getTimestamp(),
					'rev_page' => $form->getPage()->getId(),
				],
				__METHOD__,
				[],
				$revQuery[ 'joins' ]
			);
		foreach ( $revisions as $row ) {
			$affectedRevisions[ $row->rev_id ] = $this->userFactory->newFromId( (int)$row->rev_user );
		}

		$broadcast = $this->notificationBroadcastFactory->newMulti(
			'article-edit-revert',
			$form->getUser(),
			$affectedRevisions,
			[
				'url' => $this->getUserFacingUrl( $form->getPage() ),
				'message' => [
					[ 'user_note', '' ],
					[ 1, $form->getUser()->getName() ],
					[ 2, $form->getPage()->getFullText() ],
					[ 3, 1 ],
					[ 4, $this->getUserFacingUrl( $form->getPage() ) ],
					[
						5,
						$this->getUserFacingUrl( $form->getPage(), [
							'type' => 'revision',
							'oldid' => $oldRev->getId(),
							'diff' => $newRev->getId(),
						] ),
					],
				],
			]
		);
		if ( $broadcast ) {
			$broadcast->transmit();
		}
	}

	/**
	 * Get a url for the title suitable for displaying to users.
	 *
	 * @param Title $title
	 *
	 * @param array $query
	 * @return string
	 */
	private function getUserFacingUrl( Title $title, array $query = [] ): string {
		return $this->urlUtilityService->forceHttps( $title->getFullURL( $query ) );
	}

	/** @inheritDoc */
	public function onAbortEmailNotification( $editor, $title, $rc ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ||
			!$this->config->get( 'ReverbEnableWatchlistHandling' ) ) {
			return true;
		}

		// This hook comes from RecentChanges save, where as part of page saving the recent changes row is being
		// written. If the write rolls back, we shouldn't notify; additionally, this does all the service calls
		// in post-output.
		$this->loadBalancer->getConnection( DB_PRIMARY )
			->onTransactionCommitOrIdle( static fn() => $this->sendNotificationsForEdit( $editor, $title, $rc ) );

		return false;
	}

	/**
	 * Send watch list notifications.
	 *
	 * @param User $editor The owner of the watch page.
	 * @param Title $title The title of the edited page.
	 * @param RecentChange $recentChange The recentchanges object.
	 */
	private function sendNotificationsForEdit( User $editor, Title $title, RecentChange $recentChange ): void {
		$comment = $recentChange->mAttribs[ 'rc_comment' ];
		$curId = $recentChange->mAttribs[ 'rc_cur_id' ] ?? 0;
		$diffOldId = $recentChange->mAttribs[ 'rc_this_oldid' ];
		$prevOldId = $recentChange->mAttribs[ 'rc_last_oldid' ];
		$timestamp = isset( $recentChange->mAttribs[ 'rc_timestamp' ] ) ?
			MWTimestamp::convert( TS_ISO_8601, $recentChange->mAttribs[ 'rc_timestamp' ] ) :
			MWTimestamp::now( TS_ISO_8601 );

		$canonicalUrl = $this->getUserFacingUrl( $title, [
			'curid' => $curId,
			'diff' => $diffOldId,
			'oldid' => $prevOldId,
		] );

		$watchers = $this->getWatchersForChange( $recentChange );

		foreach ( $watchers as $watchingUser ) {
			if ( !$watchingUser || $watchingUser->isAnon() ) {
				continue;
			}

			$watchingLang = $this->languageFactory->getLanguage(
				$this->userOptionsLookup->getOption( $watchingUser, 'language' )
			);
			$userDateAndTime = $watchingLang->userTimeAndDate( $timestamp, $watchingUser );
			$broadcast = $this->notificationBroadcastFactory->new(
				'article-edit-watch',
				$editor,
				$watchingUser,
				[
					'url' => $canonicalUrl,
					'message' => [
						[ 'user_note', isset( $comment ) ? htmlentities( $comment, ENT_QUOTES ) : '' ],
						[ 1, $this->getAgentPageUrl( $editor ) ],
						[ 2, $editor->getName() ],
						[ 3, $this->getUserFacingUrl( $title ) ],
						[ 4, $title->getFullText() ],
						[ 5, $canonicalUrl ],
						[ 6, $userDateAndTime ],
						[ 7, $timestamp ],
						[ 8, $watchingUser->getDatePreference() ],
					],
				]
			);
			$broadcast->transmit();
		}
	}

	/**
	 * Get the watching users who should be notified of a change.
	 *
	 * @param RecentChange $change The change information
	 *
	 * @return User[]
	 */
	private function getWatchersForChange( RecentChange $change ): array {
		$minorEdit = $change->mAttribs[ 'rc_minor' ];
		$timestamp = $change->mAttribs[ 'rc_timestamp' ];
		$title = Title::castFromPageReference( $change->getPage() );
		$editor = $this->userFactory->newFromUserIdentity( $change->getPerformerIdentity() );

		if ( $title->getNamespace() < 0 ) {
			return [];
		}

		// update wl_notificationtimestamp for watchers
		$watcherIds = [];
		if ( $this->config->get( 'EnotifWatchlist' ) || $this->config->get( 'ShowUpdatedMarker' ) ) {
			$watcherIds = $this->watchedItemStore->updateNotificationTimestamp( $editor, $title, $timestamp );
		}

		$userTalkId = false;
		$users = [];
		$usersNotifiedOnAllChanges = $this->config->get( 'UsersNotifiedOnAllChanges' );
		if ( !$minorEdit || ( $this->config->get( 'EnotifMinorEdits' ) && !$editor->isAllowed( 'nominornewtalk' ) ) ) {
			if ( $title->getNamespace() === NS_USER_TALK ) {
				$targetUser = $this->userFactory->newFromName( $title->getText() );
				$userTalkId = $targetUser->getId();
			}

			if ( $this->config->get( 'EnotifWatchlist' ) ) {
				// Send updates to watchers other than the current editor
				// and don't send to watchers who are blocked and cannot login
				$watchUsers = UserArray::newFromIDs( $watcherIds );

				foreach ( $watchUsers as $watchingUser ) {
					if (
						( !$minorEdit || $this->userOptionsLookup->getOption( $watchingUser, 'enotifminoredits' ) ) &&
						$watchingUser->isEmailConfirmed() &&
						$watchingUser->getId() !== $userTalkId &&
						!in_array( $watchingUser->getName(), $usersNotifiedOnAllChanges, true ) &&
						!( $this->config->get( 'BlockDisablesLogin' ) &&
							$this->userInfo->isBlockedSitewide( $watchingUser ) )
					) {
						$users[] = $watchingUser;
					}
				}
			}
		}

		foreach ( $usersNotifiedOnAllChanges as $name ) {
			if ( $editor->getName() === $name ) {
				continue;
			}
			$users[] = $this->userFactory->newFromName( $name );
		}

		return $users;
	}

	/** @inheritDoc */
	public function onEmailUserComplete( $to, $from, $subject, $text ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ) {
			return;
		}

		$fromUserTitle = Title::makeTitle( NS_USER, $from->name );

		// strip the auto footer from email preview
		$autoFooter = "\n\n-- \n" . wfMessage( 'emailuserfooter', $from->name, $to->name )->inContentLanguage()->text();
		$textWithoutFooter = preg_replace( '/' . preg_quote( $autoFooter, '/' ) . '$/', '', $text );

		$broadcast = $this->notificationBroadcastFactory->newSingle(
			'user-interest-email-user',
			$this->userFactory->newFromName( $from->name ),
			$this->userFactory->newFromName( $to->name ),
			[
				'url' => $this->getUserFacingUrl( SpecialPage::getTitleFor( 'EmailUser' ) ),
				'message' => [
					[ 'user_note', mb_strimwidth( $textWithoutFooter, 0, 200, '...' ) ],
					[ 1, $from->name ],
					[ 2, $to->name ],
					[ 3, $subject ],
					[ 4, $this->getUserFacingUrl( $fromUserTitle ) ],
				],
			]
		);
		if ( $broadcast ) {
			$broadcast->transmit();
		}
	}

	/**
	 * @return string User-facing URL of the desired user page.
	 */
	private function getAgentPageUrl( User $agent ): string {
		if ( !$agent->getId() ) {
			return $this->urlUtilityService->forceHttps(
				SpecialPage::getTitleFor( 'Contributions', $agent->getName() )->getFullURL()
			);
		}

		return $this->urlUtilityService->forceHttps(
			Title::newFromText( $agent->getName(), NS_USER )->getFullURL()
		);
	}
}
