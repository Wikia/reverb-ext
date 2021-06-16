<?php
/**
 * Reverb
 * Special:Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Reverb\Special;

use Reverb\Traits\NotificationListTrait;
use Reverb\TwiggyWiring;
use SpecialPage;

class SpecialNotifications extends SpecialPage {
	use NotificationListTrait;

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
		$this->requireLogin();

		$twig = TwiggyWiring::init();
		$template = $twig->load($this->getContext()->getSkin()->getSkinName() === 'fandomdesktop'
			? '@Reverb/special_notifications_fandomdesktop.twig'
			: '@Reverb/special_notifications.twig');

		$groups = self::getNotificationsGroupedByPreference($this->getUser());

		// Additional Scrips for the Notification Page
		if ($this->output->getContext()->getSkin()->getSkinName() === 'fandomdesktop') {
			$this->output->addModuleStyles('ext.reverb.specialNotifications.fandomdesktop.styles');
		} else {
			$this->output->addModuleStyles('ext.reverb.notifications.styles.notificationPage');
		}

		$this->output->addModules('ext.reverb.notifications.scripts.notificationPage');
		$this->output->setPageTitle(wfMessage('notifications')->escaped());

		$this->output->addHtml($template->render(['groups' => $groups]));
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
		return 'users';
	}
}
