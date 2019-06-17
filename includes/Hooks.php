<?php
/**
 * Reverb
 * Hooks
 * Includes MIT licensed code from Extension:Echo.
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 **/

declare(strict_types=1);

namespace Reverb;

use Content;
use LinksUpdate;
use MediaWiki\MediaWikiServices;
use MWNamespace;
use OutputPage;
use Reverb\Notification\NotificationBroadcast;
use Revision;
use SkinTemplate;
use SpecialPage;
use Status;
use Title;
use User;
use WikiPage;

class Hooks {
	/**
	 * Store last reverted revision
	 *
	 * @var Revision
	 */
	protected static $lastRevertedRevision;

	/**
	 * Handler for PageContentSaveComplete hook
	 *
	 * @param WikiPage $wikiPage   WikiPage modified
	 * @param User     $user       User performing the modification
	 * @param Content  $content    New content, as a Content object
	 * @param string   $summary    Edit summary/comment
	 * @param boolean  $isMinor    Whether or not the edit was marked as minor
	 * @param boolean  $isWatch    (No longer used)
	 * @param string   $section    (No longer used)
	 * @param integer  $flags      Flags passed to WikiPage::doEditContent()
	 * @param Revision $revision   Revision object of the saved content.  If the save did not result in the creation
	 *                             of a new revision (e.g. the submission was equal to the latest revision), this
	 *                             parameter may be null (null edits, or "no-op").
	 * @param Status   $status     Status object about to be returned by doEditContent()
	 * @param integer  $baseRevId  the rev ID (or false) this edit was based on
	 * @param integer  $undidRevId the rev ID (or 0) this edit undid - added in MW 1.30
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @return boolean True
	 */
	public static function onPageContentSaveComplete(
		WikiPage &$wikiPage,
		User &$user,
		Content $content,
		string $summary,
		bool $isMinor,
		?bool $isWatch,
		?string $section,
		int &$flags,
		$revision,
		Status &$status,
		$baseRevId,
		int $undidRevId = 0
	): bool {
		if (!$revision) {
			return true;
		}

		if (!$status->isGood()) {
			return true;
		}

		$title = $wikiPage->getTitle();

		if ($title->getNamespace() == NS_USER_TALK) {
			$notifyUser = User::newFromName($title->getText());
			// If the recipient is a valid non-anonymous user and hasn't turned off their
			// notifications, generate a talk page post Echo notification.
			if ($notifyUser && $notifyUser->getId()) {
				// If this is a minor edit, only notify if the agent doesn't have talk page
				// minor edit notification blocked.
				if (!$revision->isMinor() || !$user->isAllowed('nominornewtalk')) {
					// @TODO: Fix user note.
					$broadcast = NotificationBroadcast::newSingle(
						'user-interest-talk-page-edit',
						$user,
						$notifyUser,
						[
							'url' => $title->getFullURL(),
							'message' => [
								[
									'user_note',
									''
								],
								[
									1,
									$user->getName()
								]
							]
						]
					);
					if ($broadcast) {
						$broadcast->transmit();
					}
				}
			}
		}

		// Reverted edits $undidRevId.
		if ($undidRevId > 0) {
			$undidRevision = Revision::newFromId($undidRevId);
			if ($undidRevision && $undidRevision->getTitle()->equals($title)) {
				$notifyUser = $undidRevision->getRevisionRecord()->getUser();
				if ($notifyUser && $notifyUser->getId()) {
					// @TODO: Fix user note and count reverted revisions.
					$broadcast = NotificationBroadcast::newSingle(
						'article-edit-revert',
						$user,
						$notifyUser,
						[
							'url' => $title->getFullURL(),
							'message' => [
								[
									'user_note',
									''
								],
								[
									1,
									$user->getName()
								],
								[
									2,
									$title->getFullText()
								],
								[
									3,
									1
								]
							]
						]
					);
					if ($broadcast) {
						$broadcast->transmit();
					}
				}
			}
		}

		return true;
	}

	/**
	 * Handler for LocalUserCreated hook.
	 *
	 * @param User    $user        User object that was created.
	 * @param boolean $autocreated True when account was auto-created
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 *
	 * @return boolean
	 */
	public static function onLocalUserCreated(User $user, bool $autocreated): bool {
		if (!$autocreated) {
			// @TODO: Fix user note.
			$broadcast = NotificationBroadcast::newSingle(
				'user-interest-welcome',
				$user,
				$notifyUser,
				[
					'url' => $title->getFullURL(),
					'message' => [
						[
							'user_note',
							''
						],
						[
							1,
							$user->getName()
						]
					]
				]
			);
			if ($broadcast) {
				$broadcast->transmit();
			}
		}

		return true;
	}

	/**
	 * Handler for UserGroupsChanged hook.
	 *
	 * @param User        $target    user that was changed
	 * @param array       $add       strings corresponding to groups added
	 * @param array       $remove    strings corresponding to groups removed
	 * @param User|bool   $performer
	 * @param string|bool $reason    Reason given by the user changing the rights
	 * @param array       $oldUGMs
	 * @param array       $newUGMs
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 *
	 * @return boolean
	 */
	public static function onUserGroupsChanged(
		$target,
		$add,
		$remove,
		$performer,
		$reason = false,
		array $oldUGMs = [],
		array $newUGMs = []
	): bool {
		if (!$performer) {
			// TODO: Implement support for autopromotion
			return true;
		}

		if (!$target instanceof User) {
			// TODO: Support UserRightsProxy
			return true;
		}

		if ($target->equals($performer)) {
			// Don't notify for self changes.
			return true;
		}

		// If any old groups are in $add, those groups are having their expiry
		// changed, not actually being added
		$expiryChanged = [];
		$reallyAdded = [];
		foreach ($add as $group) {
			if (isset($oldUGMs[$group])) {
				$expiryChanged[] = $group;
			} else {
				$reallyAdded[] = $group;
			}
		}

		$url = Title::newFromText($target->getName(), NS_USER)->getFullURL();
		if ($expiryChanged) {
			// @TODO: Fix user note.
			$broadcast = NotificationBroadcast::newSingle(
				'user-account-groups-expiration-change',
				$performer,
				$target,
				[
					'url' => $url,
					'message' => [
						[
							'user_note',
							''
						],
						[
							1,
							$target->getName()
						],
						[
							2,
							implode(', ', $expiryChanged)
						],
						[
							3,
							count($expiryChanged)
						]
					]
				]
			);
			if ($broadcast) {
				$broadcast->transmit();
			}
		}

		if ($reallyAdded || $remove) {
			$broadcast = NotificationBroadcast::newSingle(
				'user-account-groups-changed',
				$performer,
				$target,
				[
					'url' => $url,
					'message' => [
						[
							'user_note',
							(count($reallyAdded) ? wfMessage(
								'user-note-user-account-groups-changed-added',
								implode(', ', $reallyAdded)
							)->parse() .
							(count($remove) ? "\n" : '') : '') .
							(count($remove) ? wfMessage(
								'user-note-user-account-groups-changed-removed',
								implode(', ', $remove)
							)->parse() : '')
						],
						[
							1,
							$target->getName()
						]
					]
				]
			);
			if ($broadcast) {
				$broadcast->transmit();
			}
		}

		return true;
	}

	/**
	 * Handler for LinksUpdateAfterInsert hook.
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param string      $table
	 * @param array       $insertions
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateAfterInsert
	 *
	 * @return boolean True
	 */
	public static function onLinksUpdateAfterInsert(LinksUpdate $linksUpdate, string $table, array $insertions): bool {
		global $wgRequest;

		// @FIXME: This doesn't work in 1.27+
		// Rollback or undo should not trigger link notification
		// @TODO: Implement a better solution so it doesn't depend on the checking of
		// a specific set of request variables
		if ($wgRequest->getVal('wpUndidRevision') || $wgRequest->getVal('action') == 'rollback') {
			return true;
		}

		// Handle only
		// 1. inserts to pagelinks table &&
		// 2. content namespace pages &&
		// 3. non-transcluding pages &&
		// 4. non-redirect pages
		if ($table !== 'pagelinks'
			|| !MWNamespace::isContent($linksUpdate->getTitle()->getNamespace())
			|| !$linksUpdate->mRecursive
			|| $linksUpdate->getTitle()->isRedirect()
		) {
			return true;
		}

		$agent = $linksUpdate->getTriggeringUser();

		$revid = $linksUpdate->getRevision() ? $linksUpdate->getRevision()->getId() : null;

		foreach ($insertions as $page) {
			if (MWNamespace::isContent($page['pl_namespace'])) {
				$title = Title::makeTitle($page['pl_namespace'], $page['pl_title']);
				if ($title->isRedirect()) {
					continue;
				}

				// @TODO: Fix note, but do we desire this note system?  This breaks localization.
				$broadcast = NotificationBroadcast::newSingle(
					'user-interest-page-linked',
					$agent,
					$notifyUser,
					[
						'url' => $title->getFullURL(),
						'message' => [
							[
								'user_note',
								wfMessage(
									'user-note-user-interest-page-linked',
									$linksUpdate->getTitle()->getFullText(),
									$title->getFullText(),
									$agent->getName()
								)->parse()
							],
							[
								1,
								$linksUpdate->getTitle()->getFullText() // From
							],
							[
								2,
								$title->getFullText() // To
							]
						]
					]
				);
				if ($broadcast) {
					$broadcast->transmit();
				}
			}
		}

		return true;
	}

	/**
	 * Handler for ArticleRollbackComplete hook.
	 *
	 * @param WikiPage $wikiPage    The article that was edited
	 * @param User     $agent       The user who did the rollback
	 * @param Revision $newRevision The revision the page was reverted back to
	 * @param Revision $oldRevision The revision of the top edit that was reverted
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleRollbackComplete
	 *
	 * @return boolean True
	 */
	public static function onArticleRollbackComplete(
		WikiPage $wikiPage,
		User $agent,
		Revision $newRevision,
		Revision $oldRevision
	): bool {
		$notifyUser = $oldRevision->getRevisionRecord()->getUser();
		$latestRevision = $wikiPage->getRevision();
		self::$lastRevertedRevision = $latestRevision;

		// Skip anonymous users and null edits.
		if ($notifyUser && $notifyUser->getId() && !$oldRevision->getContent()->equals($newRevision->getContent())) {
			// @TODO: Fix user note and count reverted revisions.  Echo defaulted to plural/2 for rollback.
			$title = $wikiPage->getTitle();
			$broadcast = NotificationBroadcast::newSingle(
				'article-edit-revert',
				$agent,
				$notifyUser,
				[
					'url' => $title->getFullURL(),
					'message' => [
						[
							'user_note',
							''
						],
						[
							1,
							$notifyUser->getName()
						],
						[
							2,
							$title->getFullText()
						],
						[
							3,
							2
						],
						[
							4,
							$agent->getName()
						]
					]
				]
			);
			if ($broadcast) {
				$broadcast->transmit();
			}
		}

		return true;
	}

	/**
	 * Shoehorn the javascript and styles for reverb into every page.
	 *
	 * @param OutputPage   $output Mediawiki Output Object
	 * @param SkinTemplate $skin   Mediawiki Skin Object
	 *
	 * @return boolean True
	 */
	public static function onBeforePageDisplay(OutputPage &$output, SkinTemplate &$skin) {
		$output->addModuleStyles('ext.reverb.notifications.styles');
		$output->addModules('ext.reverb.notifications.scripts');

		return true;
	}

	/**
	 * Register the Twig template location with TwiggyService
	 *
	 * @param SpecialPage $special
	 * @param string      $subPage the subpage string or null if no subpage was specified
	 *
	 * @return void
	 */
	public static function onSpecialPageBeforeExecute(SpecialPage $special, $subPage) {
		$twig = MediaWikiServices::getInstance()->getService('TwiggyService');
		$twig->setTemplateLocation('Reverb', __DIR__ . '/../resources/templates');
	}
}
