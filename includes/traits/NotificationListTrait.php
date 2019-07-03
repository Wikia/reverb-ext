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
use GlobalVarConfig;
use MediaWiki\MediaWikiServices;

trait NotificationListTrait {
	/**
	 * Get ReverbNotifications
	 *
	 * @return void
	 */
	public static function getNotificationList() {
		return self::getNotificationConfig()->get('ReverbNotifications');
	}

	/**
	 * Get Preference Columns
	 *
	 * @return void
	 */
	public static function getNotifiers() {
		return self::getNotificationConfig()->get('ReverbNotifiers');
	}

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
		if ($group == 'email' && $user->getOption('reverb-email-frequency') == 0) {
			return false;
		}
		$type = self::replaceTypeWithUsePreference($type);
		return $user->getBoolOption(self::getPreferenceKey($type, $group));
	}

	/**
	 * Get the preference key
	 *
	 * @param string $type
	 * @param string $group
	 *
	 * @return string
	 */
	public static function getPreferenceKey(string $type, string $group): string {
		$sub = self::getSubCategoryFromType($type);
		$name = self::getNotificationName($type);
		return "reverb-{$sub}-{$group}-{$name}";
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
			if (!self::isNotificationAllowedForUser($user, $notification)
			|| self::isUsingAnotherPreference($notification)) {
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
	 * Determine if another preference is used to control this notification
	 *
	 * @param array $notification
	 *
	 * @return boolean
	 */
	public static function isUsingAnotherPreference(array $notification): bool {
		return isset($notification['use-preference']);
	}

	/**
	 * Get the category portion of a notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getCategoryFromType(string $type): string {
		return self::getTypeParts($type, 1);
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
	 * Get the Configuration container
	 *
	 * @return GlobalVarConfig
	 */
	private static function getNotificationConfig() {
		return MediaWikiServices::getInstance()->getMainConfig();
	}

	/**
	 * Get the sub category portion of a notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getSubCategoryFromType(string $type): string {
		return self::getTypeParts($type, 2);
	}

	/**
	 * Handle getting parts of a hyphenated string
	 *
	 * @param string  $type
	 * @param integer $offset
	 *
	 * @return string
	 */
	private static function getTypeParts(string $type, int $offset): string {
		return implode("-", array_slice(explode('-', $type), 0, $offset));
	}

	/**
	 * Replace type with the use-preference key
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private static function replaceTypeWithUsePreference(string $type): string {
		$notifications = self::getNotificationList();
		if (isset($notifications[$type]) && self::isUsingAnotherPreference($notifications[$type])) {
			return $notifications[$type]["use-preference"];
		}
		return $type;
	}
}
