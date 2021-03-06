<?php
/**
 * Reverb
 * User ID Helper
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

declare(strict_types=1);

namespace Reverb;

use User;

class UserIdHelper {
	/**
	 * Get the user ID for this user in the Reverb service.
	 *
	 * @param User $user
	 *
	 * @return integer
	 */
	public static function getUserIdForService(User $user): int {
		return $user->getId();
	}

	/**
	 * Get a local User object for this user ID in the Reverb service.
	 *
	 * @param integer $serviceUserId
	 *
	 * @return User|null
	 */
	public static function getUserForServiceUserId(int $serviceUserId): ?User {
		return User::newFromId($serviceUserId);
	}
}
