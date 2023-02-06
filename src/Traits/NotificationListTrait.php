<?php
/**
 * Reverb
 * UserContext Trait
 *
 * @package Reverb
 * @author  Samuel Hilson
 * @license GPL-2.0-or-later
 */

namespace Reverb\Traits;

use Config;
use MediaWiki\MediaWikiServices;
use User;

trait NotificationListTrait {
	/**
	 * Get ReverbNotifications
	 *
	 * @return array
	 */
	public static function getNotificationList(): array {
		return self::getNotificationConfig()->get( 'ReverbNotifications' );
	}

	/**
	 * Get Preference Columns
	 *
	 * @return array
	 */
	public static function getNotifiers(): array {
		return self::getNotificationConfig()->get( 'ReverbNotifiers' );
	}

	/**
	 * Get the User preference for notification
	 *
	 * @param User $user
	 * @param string $type
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function shouldNotify( User $user, string $type, string $group ): bool {
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();

		if ( $group == 'email' && $userOptionsLookup->getOption( $user,  'reverb-email-frequency' ) == 0 ) {
			return false;
		}
		$type = self::replaceTypeWithUsePreference( $type );

		return $userOptionsLookup->getBoolOption( $user, self::getPreferenceKey( $type, $group ) );
	}

	/**
	 * Get the preference key
	 *
	 * @param string $type
	 * @param string $group
	 *
	 * @return string
	 */
	public static function getPreferenceKey( string $type, string $group ): string {
		$sub = self::getSubCategoryFromType( $type );
		$name = self::getNotificationName( $type );

		return "reverb-{$sub}-{$group}-{$name}";
	}

	/**
	 * Get the default preference for notification
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public static function getDefaultPreference( $options ): array {
		$email = $options['defaults']['email'] ?? false;
		$web = $options['defaults']['web'] ?? true;

		return [ $email, $web ];
	}

	/**
	 * Handle category list organization
	 *
	 * @param User $user
	 * @param array $notificationList
	 *
	 * @return array
	 */
	public static function organizeNotificationList( User $user, array $notificationList ): array {
		$ordered = [];
		foreach ( $notificationList as $key => $notification ) {
			if ( !self::isNotificationAllowedForUser( $user, $notification ) ||
				 self::isUsingAnotherPreference( $notification ) || !self::shouldBeInMatrix( $notification ) ) {
				continue;
			}
			$value['key'] = $key;
			$value['name'] = self::getNotificationName( $key );
			$ordered[self::getSubCategoryFromType( $key )][$key] = $value;
		}

		return $ordered;
	}

	/**
	 * Get an array of notification groups to notifications.  Good for filter groups.
	 * [
	 *     'user_wiki_claim' => ['user-account-wiki-claim-created', 'user-account-wiki-claim-pending'],
	 *     'article-edit-revert' => ['article-edit-revert']
	 * ]
	 *
	 * @param User|null $user [Optional] Only show filters that the given user can see.
	 *
	 * @return array
	 */
	public static function getNotificationsGroupedByPreference( ?User $user ): array {
		$typesRaw = self::getNotificationList();

		$groups = [];
		foreach ( $typesRaw as $type => $data ) {
			if ( $user !== null && !self::isNotificationAllowedForUser( $user, $data ) ) {
				continue;
			}
			if ( isset( $data['use-preference'] ) ) {
				$groups[$data['use-preference']][] = $type;
			} else {
				$groups[$type][] = $type;
			}
		}

		ksort( $groups );

		return $groups;
	}

	/**
	 * Check user permission for a notification
	 *
	 * @param User $user
	 * @param array $notification
	 *
	 * @return bool
	 */
	public static function isNotificationAllowedForUser( User $user, array $notification ): bool {
		if ( !isset( $notification['requires'] ) ) {
			return true;
		}

		$effectiveGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserEffectiveGroups( $user );
		return boolval( array_intersect( $effectiveGroups, $notification['requires'] ) );
	}

	/**
	 * Check if a preference should be in the preference matrix
	 *
	 * @param array $notification
	 *
	 * @return bool
	 */
	public static function shouldBeInMatrix( array $notification ): bool {
		if ( isset( $notification['matrix'] ) ) {
			return boolval( $notification['matrix'] );
		}

		return true;
	}

	/**
	 * Determine if another preference is used to control this notification
	 *
	 * @param array $notification
	 *
	 * @return bool
	 */
	public static function isUsingAnotherPreference( array $notification ): bool {
		return isset( $notification['use-preference'] );
	}

	/**
	 * Get the category portion of a notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getCategoryFromType( string $type ): string {
		return self::getTypeParts( $type, 1 );
	}

	/**
	 * Get the last part of o notification type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getNotificationName( string $type ): string {
		return implode( "-", array_slice( explode( '-', $type ), 2 ) );
	}

	/**
	 * Get the Configuration container
	 *
	 * @return Config
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
	public static function getSubCategoryFromType( string $type ): string {
		return self::getTypeParts( $type, 2 );
	}

	/**
	 * Handle getting parts of a hyphenated string
	 *
	 * @param string $type
	 * @param int $offset
	 *
	 * @return string
	 */
	private static function getTypeParts( string $type, int $offset ): string {
		return implode( "-", array_slice( explode( '-', $type ), 0, $offset ) );
	}

	/**
	 * Replace type with the use-preference key
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private static function replaceTypeWithUsePreference( string $type ): string {
		$notifications = self::getNotificationList();
		if ( isset( $notifications[$type] ) && self::isUsingAnotherPreference( $notifications[$type] ) ) {
			return $notifications[$type]["use-preference"];
		}

		return $type;
	}
}
