<?php
/**
 * Reverb
 * User ID Helper
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 **/

declare(strict_types=1);

namespace Reverb;

use CentralIdLookup;
use User;

class UserIdHelper {
	/**
	 * Get the user ID for this user in the Cheevos service.
	 *
	 * @param User $user
	 *
	 * @return integer
	 */
	public static function getUserIdForService(User $user): int {
		$lookup = CentralIdLookup::factory();
		return $lookup->centralIdFromLocalUser($user, CentralIdLookup::AUDIENCE_RAW);
	}

	/**
	 * Get a local User object for this user ID in the Cheevos service.
	 *
	 * @param integer $serviceUserId
	 *
	 * @return User|null
	 */
	public static function getUserForServiceUserId(int $serviceUserId): ?User {
		$lookup = CentralIdLookup::factory();
		return $lookup->localUserFromCentralId($serviceUserId);
	}
}
