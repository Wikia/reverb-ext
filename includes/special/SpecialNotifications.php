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
use MediaWiki\MediaWikiServices;

class SpecialNotifications extends SpecialPage {
	/**
	 * Main Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct('Notifications');

		$this->output = $this->getOutput();
	}

	/**
	 * Main Executor
	 *
	 * @param string|null $subpage Sub page passed in the URL.
	 *
	 * @return void [Outputs to screen]
	 */
	public function execute($subpage) {
		$twig = MediaWikiServices::getInstance()->getService('TwiggyService');
		$template = $twig->load('@Reverb/special_notifications.twig');

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$types = $config->get("ReverbNotifications");

		// Additional Scrips for the Notification Page
		$this->output->addModules('ext.reverb.notifications.scripts.notificationPage');
		$this->output->addModuleStyles('ext.reverb.notifications.styles.notificationPage');
		$this->output->setPageTitle(wfMessage('notifications')->escaped());

		$this->output->addHtml($template->render(['types' => $types]));
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
