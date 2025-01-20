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

use MediaWiki\SpecialPage\SpecialPage;
use Reverb\Notification\NotificationListService;
use Twiggy\TwiggyService;

class SpecialNotifications extends SpecialPage {
	public function __construct(
		private readonly NotificationListService $notificationListService,
		private readonly TwiggyService $twiggyService
	) {
		parent::__construct( 'Notifications' );
		$this->twiggyService->setTemplateLocation( 'Reverb', __DIR__ . '/../../resources/templates' );
	}

	/** @inheritDoc */
	public function execute( $subpage ): void {
		$this->requireLogin();

		$isFandomDesktop = $this->getContext()->getSkin()->getSkinName() === 'fandomdesktop';
		$outputPage = $this->getOutput();

		// Additional Scrips for the Notification Page
		if ( $isFandomDesktop ) {
			$outputPage->addModuleStyles( 'ext.reverb.specialNotifications.fandomdesktop.styles' );
		} else {
			$outputPage->addModuleStyles( 'ext.reverb.notifications.styles.notificationPage' );
		}
		$outputPage->addModules( 'ext.reverb.notifications.scripts.notificationPage' );
		$outputPage->setPageTitle( $this->msg( 'notifications' )->escaped() );

		$groups = $this->notificationListService->getNotificationsGroupedByPreference( $this->getUser() );
		$template = $this->twiggyService->load(
			$isFandomDesktop ?
				'@Reverb/special_notifications_fandomdesktop.twig' :
				'@Reverb/special_notifications.twig'
		);

		$outputPage->addHtml( $template->render( [ 'groups' => $groups ] ) );
	}

	/** @inheritDoc */
	public function isListed() {
		return $this->getUser()->isRegistered();
	}

	/** @inheritDoc */
	public function isRestricted(): bool {
		return true;
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'users';
	}
}
