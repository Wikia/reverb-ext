<?php
/**
 * Reverb
 * Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Notification;

use ArrayObject;
use CentralIdLookup;
use MWException;
use User;

class NotificationBundle extends ArrayObject {
	use \Reverb\Traits\UserContextTrait;

	/**
	 * Main Constructor
	 *
	 * @param array   $notifications Array of Reverb\Notification\Notification objects.
	 * @param integer $flags         ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS
	 * @param string  $iterator      Iterator class to use.
	 *
	 * @return void
	 */
	public function __construct(array $notifications = [], int $flags = 0, string $iterator = "ArrayIterator") {
		foreach ($notifications as $notification) {
			if (!($notification instanceof Notification)) {
				throw new MWException('Invalid item was attempted to be added to bundle.');
			}
		}
		parent::__construct($notifications, $flags, $iterator);
	}

	/**
	 * Get a bundle of notifications for an user with optional filters.
	 *
	 * @param User  $user    User object to use for look up.
	 * @param array $filters [Optional] Filters for notifications.
	 *
	 * @return NotificationBundle|null Returns null if a bad user(No global account or robot account) is passed.
	 */
	public static function getBundleForUser(User $user, array $filters = []): ?NotificationBundle {
		if ($user->isBot()) {
			return null;
		}

		$lookup = CentralIdLookup::factory();
		$globalId = $lookup->centralIdFromLocalUser($user);

		if (!empty($globalId)) {
			// @TODO: Call out to the service with $globalId and optional filters.

			// @TODO: Collect notifications into an array and construct NotificationBundle.
			$notifications = [];

			/*
			foreach (NotificationResources-returned-from-service as $key => $resource) {
				$notification = new Notification($resource);
				// Do sanity checks.
				if (all good) {
					$notifications[inferred key] = $notification;
				}
			}
			*/

			$bundle = new NotificationBundle($notifications);

			// Set user context on NotificationBundle.
			$bundle->setUser($user);
			return $bundle;
		}
		return null;
	}

	/**
	 * Get the next page of bundled notifications.
	 *
	 * @return NotificationBundle|null
	 */
	public function nextPage(): ?NotificationBundle {
		// code...
	}
}
