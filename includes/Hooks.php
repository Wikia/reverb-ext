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
use Revision;
use Status;
use User;

class Hooks {
	/**
	 * Handler for PageContentSaveComplete hook
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @param WikiPage $wikiPage   WikiPage modified
	 * @param User     $user       User performing the modification
	 * @param Content  $content    New content, as a Content object
	 * @param string   $summary    Edit summary/comment
	 * @param boolean  $isMinor    Whether or not the edit was marked as minor
	 * @param boolean  $isWatch    (No longer used)
	 * @param string   $section    (No longer used)
	 * @param integer  $flags      Flags passed to WikiPage::doEditContent()
	 * @param Revision $revision   Revision object of the saved content.  If the save did not result in the creation of a new revision (e.g. the submission was equal to the latest revision), this parameter may be null (null edits, or "no-op").
	 * @param Status   $status     Status object about to be returned by doEditContent()
	 * @param integer  $baseRevId  the rev ID (or false) this edit was based on
	 * @param integer  $undidRevId the rev ID (or 0) this edit undid - added in MW 1.30
	 *
	 * @return boolean True
	 */
	public static function onPageContentSaveComplete(WikiPage &$wikiPage, User &$user, Content $content, string $summary, bool $isMinor, bool $isWatch, string $section, int &$flags, $revision, Status &$status, $baseRevId, int $undidRevId = 0): bool {
		if (!$revision) {
			return true;
		}

		if (!$status->isGood()) {
			return true;
		}

		$title = $wikiPage->getTitle();

		// $thresholds = [ 1, 10, 100, 1000, 10000, 100000, 1000000 ];
		// Echo sends a 'thank you' notification on certain thresholds.
		// Do we wish to keep these?
		// @TODO: Create 'article-edit-thanks' Notification

		if ($title->getNamespace() == NS_USER_TALK) {
			$notifyUser = User::newFromName($title->getText());
			// If the recipient is a valid non-anonymous user and hasn't turned off their notifications, generate a talk page post Echo notification.
			if ($notifyUser && $notifyUser->getId()) {
				// If this is a minor edit, only notify if the agent doesn't have talk page minor edit notification blocked.
				if (!$revision->isMinor() || !$user->isAllowed('nominornewtalk')) {
					// @TODO: Create 'user-interest-talk-page-edit' Notification
				}
			}
		}

		// Reverted edits $undidRevId.
		if ($undidRevId > 0) {
			$undidRevision = Revision::newFromId($undidRevId);
			if ($undidRevision && $undidRevision->getTitle()->equals($title)) {
				$victimId = $undidRevision->getUser();
				if ($victimId) {
					// @TODO: Create 'article-edit-revert' Notification
				}
			}
		}
	}

	/**
	 * Handler for LocalUserCreated hook.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 *
	 * @param User    $user        User object that was created.
	 * @param boolean $autocreated True when account was auto-created
	 *
	 * @return boolean
	 */
	public static function onLocalUserCreated(User $user, bool $autocreated): bool {
		if (!$autocreated) {
			// @TODO: Create 'user-welcome' Notification
		}

		return true;
	}

	/**
	 * Handler for UserGroupsChanged hook.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 *
	 * @param User        $user      user that was changed
	 * @param string[]    $add       strings corresponding to groups added
	 * @param string[]    $remove    strings corresponding to groups removed
	 * @param User|bool   $performer
	 * @param string|bool $reason    Reason given by the user changing the rights
	 * @param array       $oldUGMs
	 * @param array       $newUGMs
	 *
	 * @return boolean
	 */
	public static function onUserGroupsChanged($user, $add, $remove, $performer, $reason = false, array $oldUGMs = [], array $newUGMs = []): bool {
		if (!$performer) {
			// TODO: Implement support for autopromotion
			return true;
		}

		if (!$user instanceof User) {
			// TODO: Support UserRightsProxy
			return true;
		}

		if ($user->equals($performer)) {
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

		if ($expiryChanged) {
			// @TODO: Create 'user-groups-expiration-change' Notification
		}

		if ($reallyAdded || $remove) {
			// @TODO: Create 'user-groups-changed' Notification
		}

		return true;
	}

	/**
	 * Handler for LinksUpdateAfterInsert hook.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateAfterInsert
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param string      $table
	 * @param array       $insertions
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
		if ($table !== 'pagelinks' || !MWNamespace::isContent($linksUpdate->getTitle()->getNamespace())	|| !$linksUpdate->mRecursive || $linksUpdate->getTitle()->isRedirect()) {
			return true;
		}

		$user = $linksUpdate->getTriggeringUser();

		$revid = $linksUpdate->getRevision() ? $linksUpdate->getRevision()->getId() : null;

		foreach ($insertions as $page) {
			if (MWNamespace::isContent($page['pl_namespace'])) {
				$title = Title::makeTitle($page['pl_namespace'], $page['pl_title']);
				if ($title->isRedirect()) {
					continue;
				}

				$linkFromPageId = $linksUpdate->getTitle()->getArticleId();
				// @TODO: Create 'user-interest-page-linked' Notification
			}
		}

		return true;
	}

	/**
	 * Handler for ArticleRollbackComplete hook.
	 *
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleRollbackComplete
	 *
	 * @param WikiPage $wikiPage    The article that was edited
	 * @param User     $agent       The user who did the rollback
	 * @param Revision $newRevision The revision the page was reverted back to
	 * @param Revision $oldRevision The revision of the top edit that was reverted
	 *
	 * @return boolean True
	 */
	public static function onArticleRollbackComplete(WikiPage $wikiPage, User $agent, Revision $newRevision, Revision $oldRevision): bool {
		$victimId = $oldRevision->getUser();
		$latestRevision = $wikiPage->getRevision();
		self::$lastRevertedRevision = $latestRevision;

		// Skip anonymous users and null edits.
		if ($victimId && !$oldRevision->getContent()->equals($newRevision->getContent())) {
			// @TODO: Create 'article-edit-revert' Notification
		}

		return true;
	}

	/**
	 * Shoehorn the javascript and styles for reverb into every page.
	 *
	 * @param object Mediawiki Output Object
	 * @param object Mediawiki Skin Object
	 *
	 * @return boolean True
	 */
	public static function onBeforePageDisplay(&$output, &$skin) {
		$output->addModuleStyles('ext.reverb.notifications.styles');
		$output->addModules('ext.reverb.notifications.scripts');

		return true;
	}

}
