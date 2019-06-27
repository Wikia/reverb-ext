<?php
/**
 * Reverb
 * UserContext Trait
 *
 * @package Reverb
 * @author  Samuel Hilson
 * @license GPL-2.0-or-later
 **/

namespace Reverb\Traits;

use User;

trait NotificationListTrait {
	/**
	 * Get the User preference for notification
	 *
	 * @param User   $user
	 * @param string $type
	 * @param string $group
	 *
	 * @return boolean
	 */
	public static function shouldNotify(User $user, string $type, string $group): bool {
		$sub = self::getSubCategoryFromType($type);
		$name = self::getNotificationName($type);
		if ($group == 'email' && $user->getOption('reverb-email-frequency') == 0) {
			return false;
		}
		return $user->getBoolOption("reverb-{$sub}-{$group}-{$name}");
	}

	/**
	 * Handle category list organization
	 *
	 * @param User  $user
	 * @param array $notificationList
	 *
	 * @return array
	 */
	public static function organizeNotificationList(User $user, array $notificationList): array {
		$ordered = [];
		foreach ($notificationList as $key => $notification) {
			if (!self::isNotificationAllowedForUser($user, $notification)) {
				continue;
			}
			$value['key'] = $key;
			$value['name'] = self::getNotificationName($key);
			$ordered[self::getSubCategoryFromType($key)][$key] = $value;
		}
		return $ordered;
	}

	/**
	 * Check user permission for a notification
	 *
	 * @param User  $user
	 * @param array $notification
	 *
	 * @return boolean
	 */
	public static function isNotificationAllowedForUser(User $user, $notification): bool {
		if (!isset($notification['requires'])) {
			return true;
		}

		return boolval(array_intersect($user->getGroups(), $notification['requires']));
	}

	/**
	 * Get the category portion of a notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getCategoryFromType(string $type): string {
		return self::getParts($type, 1);
	}

	/**
	 * Get the last part of o notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getNotificationName(string $type): string {
		return implode("-", array_slice(explode('-', $type), 2));
	}

	/**
	 * Get the sub category portion of a notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getSubCategoryFromType(string $type): string {
		return self::getParts($type, 2);
	}

	/**
	 * Handle getting parts of a hyphenated string
	 *
	 * @param string  $type
	 * @param integer $offset
	 *
	 * @return string
	 */
	private static function getParts(string $type, int $offset): string {
		return implode("-", array_slice(explode('-', $type), 0, $offset));
	}
}
