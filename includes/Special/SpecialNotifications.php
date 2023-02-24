<?php
/**
 * Reverb
 * Special:Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Special;

use Reverb\Traits\NotificationListTrait;
use Reverb\TwiggyWiring;
use SpecialPage;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use UserNotLoggedIn;

class SpecialNotifications extends SpecialPage {
	use NotificationListTrait;

	/**
	 * Main Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct( 'Notifications' );
	}

	/**
	 * Main Executor
	 *
	 * @param string|null $subpage Sub page passed in the URL.
	 *
	 * @return void [Outputs to screen]
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws UserNotLoggedIn
	 */
	public function execute( $subpage ) {
		$this->requireLogin();

		$twig = TwiggyWiring::init();
		$template = $twig->load( $this->getContext()->getSkin()->getSkinName() === 'fandomdesktop'
				? '@Reverb/special_notifications_fandomdesktop.twig' : '@Reverb/special_notifications.twig' );

		$groups = self::getNotificationsGroupedByPreference( $this->getUser() );

		// Additional Scrips for the Notification Page
		if ( $this->getOutput()->getContext()->getSkin()->getSkinName() === 'fandomdesktop' ) {
			$this->getOutput()->addModuleStyles( 'ext.reverb.specialNotifications.fandomdesktop.styles' );
		} else {
			$this->getOutput()->addModuleStyles( 'ext.reverb.notifications.styles.notificationPage' );
		}

		$this->getOutput()->addModules( 'ext.reverb.notifications.scripts.notificationPage' );
		$this->getOutput()->setPageTitle( $this->msg( 'notifications' )->escaped() );

		$this->getOutput()->addHtml( $template->render( [ 'groups' => $groups ] ) );
	}

	/**
	 * Hides special page from SpecialPages special page.
	 *
	 * @return bool
	 */
	public function isListed() {
		return $this->getUser()->isRegistered();
	}

	/**
	 * Lets others determine that this special page is restricted.
	 *
	 * @return bool
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
