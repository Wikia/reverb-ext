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

use MediaWiki\MediaWikiServices;
use Reverb\Notification\NotificationBroadcastFactory;
use Reverb\Notification\NotificationListService;
use SpecialPage;
use Twiggy\TwiggyService;

class SpecialNotifications extends SpecialPage {
	public function __construct(
		private NotificationListService $notificationListService,
		private TwiggyService $twiggyService
	) {
		parent::__construct( 'Notifications' );
		$this->twiggyService->setTemplateLocation( 'Reverb', __DIR__ . '/../../resources/templates' );
	}

	/** @inheritDoc */
	public function execute( $subpage ) {
		if ( str_starts_with( $subpage, 'send' ) ) {
			$x = MediaWikiServices::getInstance()->getService( NotificationBroadcastFactory::class );
			$x->new(
				'article-edit-revert',
				$this->getUser(),
				[ $this->getUser() ],
				[
					'url' => 'https://fandom.com',
					'message' => [
						[ 'user_note', htmlentities( 'asddasdasd', ENT_QUOTES ) ],
						[ 1, $this->getUser()->getName() ],
						[ 2, SpecialPage::getTitleFor( 'Notification' )->getFullText() ],
						[ 3, 1 ],
						[ 4, 'https://fandom.com' ],
						[ 5, 'https://fandom.com' ],
					],
				]
			)->transmit();
		}
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
