<?php
/**
 * Reverb
 * NotificationEmail
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Notification;

use Config;
use MailAddress;
use MediaWiki\User\UserOptionsLookup;
use SpecialPage;
use Twiggy\TwiggyService;
use User;

// TODO--for removal?? this class is noop as $user->email is noop action
// (FandomDisableCustomMailer is true by default and correspondig variable is not available in WikiConfig)
class NotificationEmail {
	public function __construct(
		private UserOptionsLookup $userOptionsLookup,
		private Config $config,
		private NotificationListService $notificationListService,
		private NotificationFactory $notificationFactory,
		private TwiggyService $twiggyService
	) {
		$this->twiggyService->setTemplateLocation( 'Reverb', __DIR__ . '/../../resources/templates' );
	}

	public function send( NotificationBroadcast $broadcast ): void {
		$attributes = $broadcast->getAttributes();

		$notification = $this->notificationFactory->forEmail(
			$attributes['message'],
			$attributes['type'],
			$attributes['url'],
		);

		foreach ( $broadcast->getTargets() as $user ) {
			/** @var User $user */
			if ( true || $this->notificationListService->shouldNotify( $user, $attributes[ 'type' ], 'email' ) ) {
				$userLang = $this->userOptionsLookup->getOption( $user, 'language' );
				$htmlBody = $this->getWrappedBody( $notification, $userLang );
				$body = [
					'text' => $htmlBody,
					'html' => $htmlBody,
				];

				$replyTo = new MailAddress(
					$this->config->get( 'NoReplyAddress' ),
					wfMessage( 'emailsender' )->inContentLanguage()->text()
				);

				global $wgEnableHydraFeatures;
				$old = $wgEnableHydraFeatures;
				$wgEnableHydraFeatures = true;
				$user->sendMail(
					strip_tags( (string)$notification->getHeader()->inLanguage( $userLang ) ),
					$body,
					null,
					$replyTo
				);
				$wgEnableHydraFeatures = $old;
			}
		}
	}

	private function getWrappedBody( Notification $notification, string $userLang ): string {
		$canonicalServer = $this->config->get( 'CanonicalServer' );

		$notificationFrom = wfMessage( 'reverb-email-notification-from' )
			->params( $canonicalServer, $this->config->get( 'Sitename' ) )
			->inLanguage( $userLang )
			->text();

		return $this->twiggyService->load( '@Reverb/notification_email.twig' )
			->render(
				[
					'notification_from' => $notificationFrom,
					'wgCanonicalServer' => $canonicalServer,
					'header' => (string)$notification->getHeader( true )->inLanguage( $userLang ),
					'user_note' => $notification->getUserNote(),
					'icon' => $notification->getNotificationIcon(),
					'action' => $notification->getCanonicalUrl(),
					'action_description' => wfMessage( 'reverb-email-action-description' )->inLanguage( $userLang ),
					'footer' => $this->getFooter( $userLang ),
				]
			);
	}

	private function getFooter( string $language ): string {
		$preferencesTitle = SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-reverb' );
		return wfMessage( 'reverb-email-preferences-footer', $preferencesTitle->getFullURL() )
			->inLanguage( $language )
			->text();
	}
}
