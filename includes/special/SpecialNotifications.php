<?php
/**
 * Reverb
 * Special:Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Special;

use SpecialPage;

class SpecialNotifications extends SpecialPage {
	/**
	 * Main Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct('Notifications');
	}

	/**
	 * Main Executor
	 *
	 * @param string|null $subpage Sub page passed in the URL.
	 *
	 * @return void [Outputs to screen]
	 */
	public function execute($subpage) {
	}

	/**
	 * Hides special page from SpecialPages special page.
	 *
	 * @return boolean
	 */
	public function isListed() {
		return $this->getUser()->isLoggedIn();
	}

	/**
	 * Lets others determine that this special page is restricted.
	 *
	 * @return boolean
	 */
	public function isRestricted(): bool {
		return true;
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'user';
	}
}