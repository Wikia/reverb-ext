<?php
/**
 * Reverb
 * NotificationEmail
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Notification;

use Hydrawiki\Reverb\Client\V1\Resources\Notification as NotificationResource;
use MediaWiki\MediaWikiServices;
use Reverb\Traits\NotificationListTrait;
use Reverb\TwiggyWiring;
use SpecialPage;
use User;

class NotificationEmail {
	use NotificationListTrait;

	/**
	 * The notification source of truth.
	 *
	 * @var NotificationBroadcast
	 */
	protected $broadcast = null;

	/**
	 * Main Constructor
	 *
	 * @param NotificationBroadcast $broadcast An already built broadcast.
	 *
	 * @return void
	 */
	public function __construct(NotificationBroadcast $broadcast) {
		$this->broadcast = $broadcast;
	}

	/**
	 * Get a new instance while checking for errors.
	 *
	 * @param NotificationBroadcast $broadcast An already built broadcast.
	 *
	 * @return NotificationEmail|null
	 */
	public static function newFromBroadcast(NotificationBroadcast $broadcast): ?NotificationEmail {
		if (!$broadcast->getTargets()) {
			return null;
		}

		$email = new NotificationEmail($broadcast);

		return $email;
	}

	/**
	 * Get the User context.
	 *
	 * @return array User Targets
	 */
	private function getTargets(): array {
		return $this->broadcast->getTargets();
	}

	/**
	 * Send the email(s) off.
	 *
	 * @return bool Success
	 */
	public function send(): bool {
		$attributes = $this->broadcast->getAttributes();

		$resource = new NotificationResource();
		$resource->setId('0');
		$resource->setAttributes($attributes);

		$notification = new Notification($resource);

		$targets = $this->getTargets();
		$success = 0;
		foreach ($targets as $user) {
			if ($this->shouldNotify($user, $attributes['type'], 'email')) {
				$notification->setUser($user);
				$header = (string)$notification->getHeader();
				$htmlBody = $this->getWrappedBody($notification, $user);
				$body = [
					'text' => $htmlBody,
					'html' => $htmlBody
				];

				$status = $user->sendMail(strip_tags($header), $body);
				if ($status->isGood()) {
					$success++;
				}
			} else {
				$success++;
			}
		}

		if ($success != count($targets)) {
			return false;
		}
		return true;
	}

	/**
	 * Get the body of the email wrapped in header and footer.
	 *
	 * @param Notification $notification The notification to present.
	 * @param User         $user         User context for language selection.
	 *
	 * @return string
	 */
	private function getWrappedBody(Notification $notification, User $user): string {
		$twig = TwiggyWiring::init();
		$template = $twig->load('@Reverb/notification_email.twig');

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$wgCanonicalServer = $config->get("CanonicalServer");

		$wrapped = $template->render(
			[
				'wgCanonicalServer' => $wgCanonicalServer,
				'header' => (string)$notification->getHeader(true),
				'user_note' => $notification->getUserNote(),
				'icon' => $notification->getNotificationIcon(),
				'action' => $notification->getCanonicalUrl(),
				'action_description' => wfMessage(
					'reverb-email-action-description'
				)->inLanguage($user->getOption('language')),
				'footer' => $this->getFooter($user)
			]
		);
		return (string)$wrapped;
	}

	/**
	 * Get the body of the email wrapped in header and footer.
	 *
	 * @param User $user User context for language selection.
	 *
	 * @return string Assembled HTML
	 */
	private function getFooter(User $user): string {
		$preferencesTitle = SpecialPage::getTitleFor('Preferences', false, 'mw-prefsection-reverb');
		$footer = wfMessage(
			'reverb-email-preferences-footer',
			$preferencesTitle->getFullURL()
		)->inLanguage($user->getOption('language'))->text();

		return $footer;
	}
}
