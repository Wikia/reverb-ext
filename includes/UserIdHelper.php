<?php
/**
 * Reverb
 * User ID Helper
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

declare( strict_types=1 );

namespace Reverb;

use MediaWiki\MediaWikiServices;
use User;

class UserIdHelper {
	/**
	 * Get the user ID for this user in the Reverb service.
	 *
	 * @param User $user
	 *
	 * @return int
	 */
	public static function getUserIdForService( User $user ): int {
		return $user->getId();
	}

	/**
	 * Get a local User object for this user ID in the Reverb service.
	 *
	 * @param int $serviceUserId
	 *
	 * @return User|null
	 */
	public static function getUserForServiceUserId( int $serviceUserId ): ?User {
		return MediaWikiServices::getInstance()->getUserFactory()->newFromId( $serviceUserId );
	}
}
