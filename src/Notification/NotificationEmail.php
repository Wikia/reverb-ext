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

use MailAddress;
use MediaWiki\Config\Config;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use MediaWiki\User\UserOptionsLookup;
use Twiggy\TwiggyService;

// TODO--for removal?? this class is noop as $user->email is noop action
// (FandomDisableCustomMailer is true by default and correspondig variable is not available in WikiConfig)
class NotificationEmail {
	public function __construct(
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly Config $config,
		private readonly NotificationListService $notificationListService,
		private readonly NotificationFactory $notificationFactory,
		private readonly TwiggyService $twiggyService
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
			if ( $this->notificationListService->shouldNotify( $user, $attributes[ 'type' ], 'email' ) ) {
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

				$user->sendMail(
					strip_tags( (string)$notification->getHeader()->inLanguage( $userLang ) ),
					$body,
					null,
					$replyTo
				);
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
