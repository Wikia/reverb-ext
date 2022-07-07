<?php
/**
 * Reverb
 * Hooks
 * Includes MIT licensed code from Extension:Echo.
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

declare( strict_types=1 );

namespace Reverb;

use Article;
use Exception;
use Fandom\FandomDesktop\PageHeaderActions;
use Fandom\Includes\User\UserInfo;
use Fandom\Includes\Util\UrlUtilityService;
use LinksUpdate;
use MailAddress;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\SlotRecord;
use MediaWiki\User\UserIdentity;
use MWException;
use MWTimestamp;
use OutputPage;
use RecentChange;
use RequestContext;
use Reverb\Notification\NotificationBroadcast;
use Reverb\Traits\NotificationListTrait;
use RevisionReviewForm;
use SkinTemplate;
use SpecialPage;
use Title;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use User;
use UserArray;
use UserArrayFromResult;
use WikiPage;

class Hooks {
	use NotificationListTrait;

	/**
	 * Handle extension defaults
	 *
	 * @return void
	 */
	public static function registerExtension() {
		global $wgDefaultUserOptions, $wgReverbNotifications, $wgHiddenPrefs, $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return;
		}

		foreach ( $wgReverbNotifications as $notification => $notificationData ) {
			[ $email, $web ] = self::getDefaultPreference( $notificationData );
			$wgDefaultUserOptions[self::getPreferenceKey( $notification, 'email' )] = $email;
			$wgDefaultUserOptions[self::getPreferenceKey( $notification, 'web' )] = $web;
		}

		$wgDefaultUserOptions[self::getPreferenceKey( 'user-interest-email-user', 'email' )] = 0;
		$wgDefaultUserOptions[self::getPreferenceKey( 'user-interest-email-user', 'web' )] = 1;
		$wgHiddenPrefs[] = self::getPreferenceKey( 'user-interest-email-user', 'email' );

		if ( self::shouldHandleWatchlist() ) {
			$wgHiddenPrefs[] = 'enotifusertalkpages';
			$wgHiddenPrefs[] = 'enotifwatchlistpages';
		}
	}

	/**
	 * Handler for PageContentSaveComplete hook
	 *
	 * @param WikiPage $wikiPage WikiPage modified
	 * @param UserIdentity $user
	 * @param string $summary Edit summary/comment
	 * @param int $flags Flags passed to WikiPage::doEditContent()
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 * @return bool True
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 */
	public static function onPageContentSaveComplete(
		WikiPage $wikiPage, UserIdentity $user, string $summary, int $flags, RevisionRecord $revisionRecord,
		EditResult $editResult
	): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		if ( $editResult->isNullEdit() ) {
			return true;
		}

		$title = $wikiPage->getTitle();
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$agent = $userFactory->newFromUserIdentity( $user );
		if ( $title->getNamespace() == NS_USER_TALK ) {
			$notifyUser = $userFactory->newFromName( $title->getText() );
			// If the recipient is a valid non-anonymous user and hasn't turned off their
			// notifications, generate a talk page post Echo notification.
			if ( $notifyUser && $notifyUser->getId() && !$notifyUser->equals( $user ) ) {
				// If this is a minor edit, only notify if the agent doesn't have talk page
				// minor edit notification blocked.
				if ( !$revisionRecord->isMinor() || !$agent->isAllowed( 'nominornewtalk' ) ) {
					$notifyUserTalk = Title::newFromText( $notifyUser->getName(), NS_USER_TALK );
					$broadcast = NotificationBroadcast::new( 'user-interest-talk-page-edit', $agent, $notifyUser, [
							'url' => self::getUserFacingUrl( $title ),
							'message' => [
								[
									'user_note',
									htmlentities( $summary, ENT_QUOTES ),
								],
								[
									1,
									self::getAgentPageUrl( $agent ),
								],
								[
									2,
									$agent->getName(),
								],
								[
									3,
									self::getUserFacingUrl( $notifyUserTalk ),
								],
								[
									4,
									$agent->getName(),
								],
							],
						] );
					if ( $broadcast ) {
						$broadcast->transmit();
					}
				}
			}
		}

		// Reverted edits $undidRevId.
		$undidRevId = $editResult->getNewestRevertedRevisionId();
		$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		if ( $undidRevId > 0 ) {
			$undidRevision = $revisionLookup->getRevisionById( $undidRevId );
			if ( $undidRevision && $undidRevision->getPage()->getId() === $wikiPage->getId() ) {
				$notifyUser = $userFactory->newFromUserIdentity( $undidRevision->getUser() );
				if ( $notifyUser && $notifyUser->getId() && !$notifyUser->equals( $agent ) ) {
					$broadcast = NotificationBroadcast::new( 'article-edit-revert', $agent, $notifyUser, [
							'url' => self::getUserFacingUrl( $title ),
							'message' => [
								[
									'user_note',
									htmlentities( $summary, ENT_QUOTES ),
								],
								[
									1,
									$agent->getName(),
								],
								[
									2,
									$title->getFullText(),
								],
								[
									3,
									1,
								],
								[
									4,
									self::getUserFacingUrl( $title ),
								],
								[
									5,
									self::getUserFacingUrl( $title, [
											'type' => 'revision',
											'oldid' => $undidRevId,
											'diff' => $revisionRecord->getId(),
										] ),
								],
							],
						] );
					if ( $broadcast ) {
						$broadcast->transmit();
					}
				}
			}
		}

		return true;
	}

	/**
	 * Handler for UserGroupsChanged hook.
	 *
	 * @param User $target user that was changed
	 * @param array $add strings corresponding to groups added
	 * @param array $remove strings corresponding to groups removed
	 * @param User|bool $performer
	 * @param string|bool $reason Reason given by the user changing the rights
	 * @param array $oldUGMs
	 * @param array $newUGMs
	 *
	 * @return bool
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 */
	public static function onUserGroupsChanged(
		$target, $add, $remove, $performer, $reason = false, array $oldUGMs = [], array $newUGMs = []
	): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		if ( !$performer ) {
			// TODO: Implement support for autopromotion
			return true;
		}

		if ( !$target instanceof User ) {
			// TODO: Support UserRightsProxy
			return true;
		}

		if ( $target->equals( $performer ) ) {
			// Don't notify for self changes.
			return true;
		}

		// If any old groups are in $add, those groups are having their expiry
		// changed, not actually being added
		$expiryChanged = [];
		$reallyAdded = [];
		foreach ( $add as $group ) {
			if ( isset( $oldUGMs[$group] ) ) {
				$expiryChanged[] = $group;
			} else {
				$reallyAdded[] = $group;
			}
		}

		$url = self::getUserFacingUrl( Title::newFromText( $target->getName(), NS_USER ) );
		if ( $expiryChanged ) {
			// @TODO: Fix user note.
			$broadcast =
				NotificationBroadcast::newSingle( 'user-account-groups-expiration-change', $performer, $target, [
						'url' => $url,
						'message' => [
							[
								'user_note',
								'',
							],
							[
								1,
								$target->getName(),
							],
							[
								2,
								implode( ', ', $expiryChanged ),
							],
							[
								3,
								count( $expiryChanged ),
							],
						],
					] );
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}

		if ( $reallyAdded || $remove ) {
			$broadcast = NotificationBroadcast::newSingle( 'user-account-groups-changed', $performer, $target, [
					'url' => $url,
					'message' => [
						[
							'user_note',
							( count( $reallyAdded ) ? wfMessage( 'user-note-user-account-groups-changed-added',
									implode( ', ', $reallyAdded ) )->parse() . ( count( $remove ) ? "\n" : '' ) : '' ) .
							( count( $remove ) ? wfMessage( 'user-note-user-account-groups-changed-removed',
								implode( ', ', $remove ) )->parse() : '' ),
						],
						[
							1,
							$target->getName(),
						],
					],
				] );
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}

		return true;
	}

	/**
	 * Handler for LinksUpdateAfterInsert hook.
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param string $table
	 * @param array $insertions
	 *
	 * @return bool True
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateAfterInsert
	 */
	public static function onLinksUpdateAfterInsert( LinksUpdate $linksUpdate, string $table, array $insertions
	): bool {
		global $wgRequest, $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		// @FIXME: This doesn't work in 1.27+
		// Rollback or undo should not trigger link notification
		// @TODO: Implement a better solution so it doesn't depend on the checking of
		// a specific set of request variables
		if ( $wgRequest->getVal( 'wpUndidRevision' ) || $wgRequest->getVal( 'action' ) == 'rollback' ) {
			return true;
		}

		// Handle only
		// 1. inserts to pagelinks table &&
		// 2. content namespace pages &&
		// 3. non-transcluding pages &&
		// 4. non-redirect pages
		$nsInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
		if ( $table !== 'pagelinks' || !$nsInfo->isContent( $linksUpdate->getTitle()->getNamespace() ) ||
			 !$linksUpdate->mRecursive || $linksUpdate->getTitle()->isRedirect() ) {
			return true;
		}

		$agent = MediaWikiServices::getInstance()
			->getUserFactory()
			->newFromUserIdentity( $linksUpdate->getTriggeringUser() );

		$db = wfGetDB( DB_REPLICA );
		foreach ( $insertions as $page ) {
			if ( $nsInfo->isContent( $page['pl_namespace'] ) ) {
				$linkToTitle = Title::makeTitle( $page['pl_namespace'], $page['pl_title'] );
				if ( $linkToTitle->isRedirect() ) {
					continue;
				}

				$userIds = $db->selectFieldValues( 'watchlist', 'wl_user', [
						'wl_user != ' . intval( $agent->getId() ),
						'wl_namespace' => $page['pl_namespace'],
						'wl_title' => $page['pl_title'],
					], __METHOD__ );

				$targets = UserArrayFromResult::newFromIDs( $userIds );
				$notifyUsers = [];
				foreach ( $targets as $target ) {
					$notifyUsers[] = $target;
				}

				$broadcast = NotificationBroadcast::new( 'article-edit-page-linked', $agent, $notifyUsers, [
						'url' => self::getUserFacingUrl( $linkToTitle ),
						'message' => [
							[
								'user_note',
								'',
							],
							[
								1,
								$linksUpdate->getTitle()->getFullText(),
							],
							[
								2,
								$linkToTitle->getFullText(),
							],
							[
								3,
								self::getUserFacingUrl( $linksUpdate->getTitle() ),
							],
							[
								4,
								self::getUserFacingUrl( $linkToTitle ),
							],
							[
								5,
								self::getAgentPageUrl( $agent ),
							],
							[
								6,
								$agent->getName(),
							],
						],
					] );
				if ( $broadcast ) {
					$broadcast->transmit();
				}
			}
		}

		return true;
	}

	/**
	 * Handler for ArticleRollbackComplete hook.
	 *
	 * @param WikiPage $wikiPage The article that was edited
	 * @param UserIdentity $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 * @return bool True
	 * @throws Identifier\InvalidIdentifierException
	 * @throws MWException
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleRollbackComplete
	 */
	private static function onArticleRollbackComplete(
		WikiPage $wikiPage, UserIdentity $user, string $summary, int $flags, RevisionRecord $revisionRecord,
		EditResult $editResult
	): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		$oldRevisionId = $editResult->getNewestRevertedRevisionId();
		$oldRevision = $revisionLookup->getRevisionById( $oldRevisionId );
		$newRevisionId = $editResult->getOriginalRevisionId();
		$newRevision = $revisionLookup->getRevisionById( $newRevisionId );
		$notifyUser = $oldRevision->getUser();
		$latestRevision = $wikiPage->getRevisionRecord();

		// Skip anonymous users and null edits.
		if ( $notifyUser && $notifyUser->getId() && !$notifyUser->equals( $user ) &&
			 !$oldRevision->getContent( SlotRecord::MAIN )->equals( $newRevision->getContent( SlotRecord::MAIN ) ) ) {
			// @TODO: Fix user note and count reverted revisions.  Echo defaulted to plural/2 for rollback.
			$title = $wikiPage->getTitle();
			$broadcast = NotificationBroadcast::newSingle(
				'article-edit-revert',
				$userFactory->newFromUserIdentity( $user ),
				$userFactory->newFromUserIdentity( $notifyUser ),
				[
					'url' => self::getUserFacingUrl( $title ),
					'message' => [
						[
							'user_note',
							'',
						],
						[
							1,
							$notifyUser->getName(),
						],
						[
							2,
							$title->getFullText(),
						],
						[
							3,
							2,
						],
						[
							4,
							self::getUserFacingUrl( $title ),
						],
						[
							5,
							self::getUserFacingUrl( $title, [
									'type' => 'revision',
									'oldid' => $oldRevision->getId(),
									'diff' => $latestRevision->getId(),
								] ),
						],
					],
				] );
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}

		return true;
	}

	/**
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public static function onPageSaveComplete(
		WikiPage $wikiPage, UserIdentity $user, string $summary, int $flags, RevisionRecord $revisionRecord,
		EditResult $editResult
	) {
		if ( $editResult->isRevert() ) {
			self::onArticleRollbackComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult );
		} else {
			self::onPageContentSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult );
		}
	}

	/**
	 * Shoehorn the javascript and styles for reverb into every page.
	 *
	 * @param OutputPage &$output Mediawiki Output Object
	 * @param SkinTemplate &$skin Mediawiki Skin Object
	 *
	 * @return bool True
	 */
	public static function onBeforePageDisplay( OutputPage &$output, SkinTemplate &$skin ) {
		$skinName = $output->getSkin()->getSkinName();

		// only load JS and styles on Special:Notifactions
		if ( !$output->getTitle()->isSpecial( 'Notifications' ) ) {
			return true;
		}

		if ( $output->getUser()->isAnon() ) {
			return true;
		}

		if ( $skinName !== 'fandomdesktop' ) {
			$output->addModuleStyles( [ 'ext.reverb.notifications.styles', 'ext.hydraCore.font-awesome.styles' ] );
		}

		$output->addModules( 'ext.reverb.notifications.scripts' );

		return true;
	}

	/**
	 * Handle setting up profile page handlers.
	 *
	 * @param Title &$title
	 * @param Article &$article
	 * @param object &$output
	 * @param User &$user
	 * @param object $request
	 * @param object $mediaWiki
	 *
	 * @return void
	 * @throws MWException
	 */
	public static function onBeforeInitialize( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return;
		}

		if ( $title->equals( SpecialPage::getTitleFor( "Preferences" ) ) ) {
			$output->addModules( 'ext.reverb.preferences' );
		}
	}

	/**
	 * Register the Twig template location with TwiggyService
	 *
	 * @param SpecialPage $special
	 * @param string $subPage the subpage string or null if no subpage was specified
	 *
	 * @return void
	 */
	public static function onSpecialPageBeforeExecute( SpecialPage $special, $subPage ) {
		TwiggyWiring::init();
	}

	/**
	 * Handler for GetNewMessagesAlert hook.
	 * We're using the GetNewMessagesAlert hook instead of the
	 * ArticleEditUpdateNewTalk hook since we still want the user_newtalk data
	 * to be updated and availble to client-side tools and the API.
	 *
	 * @param string &$newMessagesAlert An alert that the user has new messages
	 *                                     or an empty string if the user does not
	 *                                     (empty by default)
	 * @param array $newtalks This will be empty if the user has no new messages
	 *                                     or an Array containing links and revisions if
	 *                                     there are new messages
	 * @param User $user The user who is loading the page
	 * @param OutputPage $out Output object
	 *
	 * @return bool False, suppress entirely.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/GetNewMessagesAlert
	 *
	 */
	public static function onGetNewMessagesAlert( &$newMessagesAlert, $newtalks, $user, $out ): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		return false;
	}

	/**
	 * Handler for GetPreferences hook.
	 *
	 * @param User $user User to get preferences for
	 * @param array &$preferences Preferences array
	 *
	 * @return bool True in all cases.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 */
	public static function onGetPreferences( $user, &$preferences ): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		$preferences['reverb-email-frequency'] = [
			'type' => 'radio',
			'help-message' => 'reverb-pref-email-options-toggle-help',
			'section' => 'reverb/reverb-email-options-toggle',
			'options' => [
				wfMessage( 'reverb-pref-email-frequency-immediately' )->plain() => 1,
				wfMessage( 'reverb-pref-email-frequency-never' )->plain() => 0,
			],
		];

		$preferences[self::getPreferenceKey( 'user-interest-email-user', 'web' )] = [
			'type' => 'toggle',
			'label-message' => 'user-interest-email-user',
			'section' => 'reverb/email-user-notification',
		];

		// Setup Check Matrix columns
		$columns = [];
		$reverbNotifiers = self::getNotifiers();
		foreach ( $reverbNotifiers as $notifierType => $notifierData ) {
			$formatMessage = wfMessage( 'reverb-pref-' . $notifierType )->escaped();
			$columns[$formatMessage] = $notifierType;
		}

		$notifications = self::organizeNotificationList( $user, self::getNotificationList() );

		foreach ( $notifications as $group => $notificationType ) {
			$rows = [];
			$tooltips = [];

			foreach ( $notificationType as $key => $notification ) {
				$notificationTitle = wfMessage( 'reverb-pref-title-' . $key )->numParams( 1 )->escaped();
				$rows[$notificationTitle] = $notification['name'];
				$hasTooltip = !wfMessage( 'reverb-pref-tooltip-' . $key )->inContentLanguage()->isBlank();
				if ( $hasTooltip ) {
					$tooltips[$notificationTitle] = wfMessage( 'reverb-pref-tooltip-' . $key )->text();
				}
			}

			$preferences['reverb-' . $group] = [
				'class' => 'HTMLCheckMatrix',
				'section' => 'reverb/reverb-' . $group,
				'rows' => $rows,
				'columns' => $columns,
				'prefix' => 'reverb-' . $group . '-',
				'tooltips' => $tooltips,
			];
		}
		foreach ( $preferences as $index => $preference ) {
			if ( isset( $preference['section'] ) && $preference['section'] == 'personal/email' ) {
				$preferences[$index]['section'] = 'reverb/reverb-email-options';
			}

			// Reverb supercedes Fandom email preferences, so don't show them in Special:Preferences
			// Note: this depends on Reverb being loaded after fandom extensions
			if ( isset( $preference['section'] ) && strpos( $preference['section'], 'emailv2/' ) === 0 ) {
				$preferences[$index]['type'] = 'hidden';
				$preferences[$index]['section'] = 'reverb/reverb-email-options';
			}
		}

		return true;
	}

	/**
	 * Handle when FlaggedRevs reverts an edit.
	 *
	 * @param RevisionReviewForm $reviewForm The FlaggedRevs review form class.
	 * @param bool|string $status Success or message key string error.
	 *
	 * @return bool True
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public static function onFlaggedRevsRevisionReviewFormAfterDoSubmit( RevisionReviewForm $reviewForm, $status
	): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		if ( $reviewForm->getAction() === 'reject' && $status === true ) {
			// revid -> userid
			$affectedRevisions = [];
			$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
			$revisionStore = MediaWikiServices::getInstance()->getRevisionStore();
			$userFactory = MediaWikiServices::getInstance()->getUserFactory();
			$revQuery = $revisionStore->getQueryInfo();
			$article = new WikiPage( $reviewForm->getPage() );
			$newRev = $revisionLookup->getRevisionByTitle( $reviewForm->getPage(), $reviewForm->getOldId() );
			$oldRev = $revisionLookup->getRevisionByTitle( $reviewForm->getPage(), $reviewForm->getRefId() );
			$timestampField = isset( $revQuery['tables']['temp_rev_user'] ) ? 'revactor_timestamp' : 'rev_timestamp';

			$revisions =
				wfGetDB( DB_REPLICA )->select( $revQuery['tables'],
					[ 'rev_id', 'rev_user' => $revQuery['fields']['rev_user'] ], [
						'rev_id <= ' . $newRev->getId(),
						"$timestampField <= " . $newRev->getTimestamp(),
						'rev_id > ' . $oldRev->getId(),
						"$timestampField > " . $oldRev->getTimestamp(),
						'rev_page' => $article->getId(),
					], __METHOD__, [], $revQuery['joins'] );
			foreach ( $revisions as $row ) {
				$user = $userFactory->newFromId( (int)$row->rev_user );
				if ( $user !== null ) {
					$affectedRevisions[$row->rev_id] = $user;
				}
			}

			$broadcast =
				NotificationBroadcast::newMulti( 'article-edit-revert', $reviewForm->getUser(), $affectedRevisions, [
						'url' => self::getUserFacingUrl( $reviewForm->getPage() ),
						'message' => [
							[
								'user_note',
								'',
							],
							[
								1,
								$reviewForm->getUser()->getName(),
							],
							[
								2,
								$reviewForm->getPage()->getFullText(),
							],
							[
								3,
								1,
							],
							[
								4,
								self::getUserFacingUrl( $reviewForm->getPage() ),
							],
							[
								5,
								self::getUserFacingUrl( $reviewForm->getPage(), [
										'type' => 'revision',
										'oldid' => $oldRev->getId(),
										'diff' => $newRev->getId(),
									] ),
							],
						],
					] );
			if ( $broadcast ) {
				$broadcast->transmit();
			}
		}

		return true;
	}

	/**
	 * Abort all talk page emails since that is handled by Reverb now.
	 *
	 * @param User $targetUser The user of the edited talk page.
	 * @param Title $title The talk page title that was edited.
	 *
	 * @return bool False
	 */
	public static function onAbortTalkPageEmailNotification( User $targetUser, Title $title ): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		return false;
	}

	/**
	 * Get a url for the title suitable for displaying to users.
	 *
	 * @param Title $title
	 *
	 * @param array $query
	 * @return string
	 */
	private static function getUserFacingUrl( Title $title, array $query = [] ): string {
		/**
		 * @var UrlUtilityService $urlUtilityService
		 */
		$urlUtilityService = MediaWikiServices::getInstance()->getService( UrlUtilityService::class );

		return $urlUtilityService->forceHttps( $title->getFullURL( $query ) );
	}

	/**
	 * Handle RecentChanges AbortEmailNotification hook.
	 *
	 * @param User $editor The owner of the watch page.
	 * @param Title $title The title of the edited page.
	 * @param RecentChange $recentChange The recentchanges object.
	 *
	 * @return bool false if Reverb will handle watchlist notifications.
	 * @throws Exception
	 */
	public static function onAbortEmailNotification( User $editor, Title $title, RecentChange $recentChange ): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		if ( !self::shouldHandleWatchlist() ) {
			return true;
		}

		// This hook comes from RecentChanges save, where as part of page saving the recentchanges row is being
		// written.  If the write rolls back, we shoudln't notify; additionally, this does all the service calls
		// in post-output.
		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->onTransactionCommitOrIdle( function () use ( $editor, $title, $recentChange ) {
			self::sendNotificationsForEdit( $editor, $title, $recentChange );
		} );

		return false;
	}

	/**
	 * Send watch list notifications.
	 *
	 * @param User $editor The owner of the watch page.
	 * @param Title $title The title of the edited page.
	 * @param RecentChange $recentChange The recentchanges object.
	 *
	 * @return void false
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	private static function sendNotificationsForEdit( User $editor, Title $title, RecentChange $recentChange ) {
		$comment = $recentChange->mAttribs['rc_comment'];
		$timestamp = $recentChange->mAttribs['rc_timestamp'];
		$curId = $recentChange->mAttribs['rc_cur_id'] ?? 0;
		$diffOldId = $recentChange->mAttribs['rc_this_oldid'];
		$prevOldId = $recentChange->mAttribs['rc_last_oldid'];

		$canonicalUrl = self::getUserFacingUrl( $title, [
				'curid' => $curId,
				'diff' => $diffOldId,
				'oldid' => $prevOldId,
			] );

		$watchers = self::getWatchersForChange( $recentChange );
		if ( isset( $timestamp ) ) {
			$timestamp = MWTimestamp::convert( TS_ISO_8601, $timestamp );
		} else {
			$timestamp = MWTimestamp::now( TS_ISO_8601 );
		}

		foreach ( $watchers as $watchingUser ) {
			if ( !$watchingUser || $watchingUser->isAnon() ) {
				continue;
			}

			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			$langFactory = MediaWikiServices::getInstance()->getLanguageFactory();
			$watchingLang = $langFactory->getLanguage( $userOptionsLookup->getOption( $watchingUser, 'language' ) );
			$userDateAndTime = $watchingLang->userTimeAndDate( $timestamp, $watchingUser );
			$broadcast = NotificationBroadcast::new( 'article-edit-watch', $editor, $watchingUser, [
					'url' => $canonicalUrl,
					'message' => [
						[
							'user_note',
							isset( $comment ) ? htmlentities( $comment, ENT_QUOTES ) : '',
						],
						[
							1,
							self::getAgentPageUrl( $editor ),
						],
						[
							2,
							$editor->getName(),
						],
						[
							3,
							self::getUserFacingUrl( $title ),
						],
						[
							4,
							$title->getFullText(),
						],
						[
							5,
							$canonicalUrl,
						],
						[
							6,
							$userDateAndTime,
						],
						[
							7,
							$timestamp,
						],
						[
							8,
							$watchingUser->getDatePreference(),
						],
					],
				] );
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
	private static function getWatchersForChange( RecentChange $change ): array {
		global $wgUsersNotifiedOnAllChanges, $wgEnotifWatchlist, $wgBlockDisablesLogin, $wgEnotifMinorEdits,
			   $wgShowUpdatedMarker;

		$minorEdit = $change->mAttribs['rc_minor'];
		$timestamp = $change->mAttribs['rc_timestamp'];
		$title = Title::castFromPageReference( $change->getPage() );
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$userInfo = MediaWikiServices::getInstance()->getService( UserInfo::class );
		$editor = $userFactory->newFromUserIdentity( $change->getPerformerIdentity() );

		if ( $title->getNamespace() < 0 ) {
			return [];
		}

		// update wl_notificationtimestamp for watchers
		$watcherIds = [];
		if ( $wgEnotifWatchlist || $wgShowUpdatedMarker ) {
			$watcherIds = MediaWikiServices::getInstance()
					->getWatchedItemStore()
					->updateNotificationTimestamp( $editor, $title, $timestamp );
		}

		$userTalkId = false;
		$users = [];
		if ( !$minorEdit || ( $wgEnotifMinorEdits && !$editor->isAllowed( 'nominornewtalk' ) ) ) {
			if ( $title->getNamespace() == NS_USER_TALK ) {
				$targetUser = $userFactory->newFromName( $title->getText() );
				$userTalkId = $targetUser->getId();
			}

			if ( $wgEnotifWatchlist ) {
				// Send updates to watchers other than the current editor
				// and don't send to watchers who are blocked and cannot login
				$watchUsers = UserArray::newFromIDs( $watcherIds );

				foreach ( $watchUsers as $watchingUser ) {
					if ( ( !$minorEdit || $userOptionsLookup->getOption( $watchingUser, 'enotifminoredits' ) ) &&
						 $watchingUser->isEmailConfirmed() && $watchingUser->getId() !== $userTalkId &&
						 !in_array( $watchingUser->getName(), $wgUsersNotifiedOnAllChanges, true ) &&
						 !( $wgBlockDisablesLogin && $userInfo->isBlockedSitewide( $watchingUser ) ) ) {
						$users[] = $watchingUser;
					}
				}
			}
		}

		foreach ( $wgUsersNotifiedOnAllChanges as $name ) {
			if ( $editor->getName() === $name ) {
				continue;
			}
			$users[] = $userFactory->newFromName( $name );
		}

		return $users;
	}

	/**
	 * Handler for EmailUserComplete hook.
	 *
	 * @param MailAddress $address Address of receiving user
	 * @param MailAddress $from Address of sending user
	 * @param string $subject Subject of the mail
	 * @param string $text Text of the mail
	 *
	 * @return bool true in all cases
	 * @throws Identifier\InvalidIdentifierException
	 * @throws LoaderError
	 * @throws MWException
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EmailUserComplete
	 */
	public static function onEmailUserComplete( MailAddress $address, MailAddress $from, $subject, $text ): bool {
		global $wgEnableHydraFeatures;

		if ( !$wgEnableHydraFeatures ) {
			return true;
		}

		$fromUserTitle = Title::makeTitle( NS_USER, $from->name );
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();

		// strip the auto footer from email preview
		$autoFooter =
			"\n\n-- \n" . wfMessage( 'emailuserfooter', $from->name, $address->name )->inContentLanguage()->text();
		$textWithoutFooter = preg_replace( '/' . preg_quote( $autoFooter, '/' ) . '$/', '', $text );

		$broadcast =
			NotificationBroadcast::newSingle( 'user-interest-email-user', $userFactory->newFromName( $from->name ),
				$userFactory->newFromName( $address->name ), [
					'url' => self::getUserFacingUrl( SpecialPage::getTitleFor( 'EmailUser' ) ),
					'message' => [
						[
							'user_note',
							mb_strimwidth( $textWithoutFooter, 0, 200, '...' ),
						],
						[
							1,
							$from->name,
						],
						[
							2,
							$address->name,
						],
						[
							3,
							$subject,
						],
						[
							4,
							self::getUserFacingUrl( $fromUserTitle ),
						],
					],
				] );
		if ( $broadcast ) {
			$broadcast->transmit();
		}

		return true;
	}

	/**
	 * Get the user page(User:Example or Special:Contributions/127.0.0.1) for the given User object.
	 *
	 * @param User $agent The User
	 *
	 * @return string User-facing URL of the desired user page.
	 * @throws MWException
	 */
	private static function getAgentPageUrl( User $agent ): string {
		if ( !$agent->getId() ) {
			$agentPage = SpecialPage::getTitleFor( 'Contributions', $agent->getName() );
		} else {
			$agentPage = Title::newFromText( $agent->getName(), NS_USER );
		}

		return self::getUserFacingUrl( $agentPage );
	}

	/**
	 * Get whether watchlist handling is enabled.
	 *
	 * @return bool Enabled
	 */
	private static function shouldHandleWatchlist(): bool {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();

		return $mainConfig->get( 'ReverbEnableWatchlistHandling' );
	}

	/**
	 * @param Title $title
	 * @param bool &$shouldDisplay
	 * @return bool
	 */
	public static function onPageHeaderActionButtonShouldDisplay( \Title $title, bool &$shouldDisplay ): bool {
		if ( $title->isSpecial( 'Notifications' ) ) {
			$shouldDisplay = true;
		}

		return true;
	}

	/**
	 * @param $actionButton
	 * @param &$contentActions
	 * @return bool
	 * @throws MWException
	 */
	public static function onBeforePrepareActionButtons( $actionButton, &$contentActions ): bool {
		global $wgEnableHydraFeatures;

		$skinName = RequestContext::getMain()->getSkin()->getSkinName();
		$title = RequestContext::getMain()->getTitle();

		if ( $skinName === 'fandomdesktop' && $actionButton instanceof PageHeaderActions &&
			 $title->isSpecial( 'Notifications' ) ) {
			$actionButton->setCustomAction( [
				'text' => wfMessage( 'preferences' )->text(),
				'href' => SpecialPage::getTitleFor( 'Preferences', false,
					$wgEnableHydraFeatures ? 'mw-prefsection-reverb' : 'mw-prefsection-emailv2' )->getFullURL(),
				'id' => 'ca-preferences-notifications',
				'data-tracking' => 'ca-preferences-notifications',
				'icon' => 'wds-icons-gear-small',
			] );
		}

		return true;
	}
}
