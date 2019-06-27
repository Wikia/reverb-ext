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
use Reverb\Traits\NotificationListTrait;

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

		$header = $notification->getHeader(true);
		$body = [
			'text' => $header,
			'html' => $this->getWrappedBody($header)
		];

		$targets = $this->getTargets();
		$success = 0;
		foreach ($targets as $user) {
			if ($this->shouldNotify($user, $attributes['type'], 'email')) {
				$status = $user->sendMail(strip_tags((string)$header), $body);
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
	 * @param string $body The message body.
	 *
	 * @return string
	 */
	private function getWrappedBody($body): string {
		return (string)$body;
	}
}
