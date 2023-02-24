<?php
/**
 * Reverb
 * UserContext Trait
 *
 * @package Reverb
 * @author  Samuel Hilson
 * @license GPL-2.0-or-later
 */

namespace Reverb\Notification;

use Config;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserOptionsLookup;
use User;

class NotificationListService {
	public function __construct(
		private UserOptionsLookup $userOptionsLookup,
		private Config $config,
		private UserGroupManager $userGroupManager
	) {
	}

	public function shouldNotify( User $user, string $type, string $group ): bool {
		if ( $group === 'email' && $this->userOptionsLookup->getOption( $user,  'reverb-email-frequency' ) == 0 ) {
			return false;
		}
		$type = $this->replaceTypeWithUsePreference( $type );

		return $this->userOptionsLookup->getBoolOption( $user, self::getPreferenceKey( $type, $group ) );
	}

	/**
	 * Handle category list organization
	 *
	 * @param User $user
	 * @param array $notificationList
	 *
	 * @return array
	 */
	public function organizeNotificationList( User $user, array $notificationList ): array {
		$ordered = [];
		foreach ( $notificationList as $key => $notification ) {
			if ( !$this->isNotificationAllowedForUser( $user, $notification ) ||
				 $this->isUsingAnotherPreference( $notification ) || !$this->shouldBeInMatrix( $notification ) ) {
				continue;
			}
			$value['key'] = $key;
			$value['name'] = self::getNotificationName( $key );
			$ordered[self::getSubCategoryFromType( $key )][$key] = $value;
		}

		return $ordered;
	}

	/**
	 * Get an array of notification groups to notifications. Good for filter groups.
	 * [
	 *     'user_wiki_claim' => ['user-account-wiki-claim-created', 'user-account-wiki-claim-pending'],
	 *     'article-edit-revert' => ['article-edit-revert']
	 * ]
	 *
	 * @param ?User $user [Optional] Only show filters that the given user can see.
	 *
	 * @return array
	 */
	public function getNotificationsGroupedByPreference( ?User $user ): array {
		$groups = [];
		foreach ( $this->config->get( 'ReverbNotifications' ) as $type => $data ) {
			if ( $user !== null && !$this->isNotificationAllowedForUser( $user, $data ) ) {
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

	public function isNotificationAllowedForUser( User $user, array $notification ): bool {
		if ( !isset( $notification['requires'] ) ) {
			return true;
		}

		$effectiveGroups = $this->userGroupManager->getUserEffectiveGroups( $user );
		return (bool)array_intersect( $effectiveGroups, $notification['requires'] );
	}

	public function shouldBeInMatrix( array $notification ): bool {
		if ( isset( $notification['matrix'] ) ) {
			return (bool)$notification['matrix'];
		}

		return true;
	}

	public function isUsingAnotherPreference( array $notification ): bool {
		return isset( $notification['use-preference'] );
	}

	public function getCategoryFromType( string $type ): string {
		return self::getTypeParts( $type, 1 );
	}

	public function replaceTypeWithUsePreference( string $type ): string {
		$notifications = $this->config->get( 'ReverbNotifications' );
		if ( isset( $notifications[$type] ) && $this->isUsingAnotherPreference( $notifications[$type] ) ) {
			return $notifications[$type]['use-preference'];
		}

		return $type;
	}

	public static function getPreferenceKey( string $type, string $group ): string {
		$sub = self::getSubCategoryFromType( $type );
		$name = self::getNotificationName( $type );

		return "reverb-{$sub}-{$group}-{$name}";
	}

	public static function getTypeParts( string $type, int $offset ): string {
		return implode( '-', array_slice( explode( '-', $type ), 0, $offset ) );
	}

	public static function getNotificationName( string $type ): string {
		return implode( '-', array_slice( explode( '-', $type ), 2 ) );
	}

	public static function getSubCategoryFromType( string $type ): string {
		return self::getTypeParts( $type, 2 );
	}
}
