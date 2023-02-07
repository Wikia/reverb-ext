<?php
/**
 * Reverb
 * ReverbInit
 * Includes MIT licensed code from Extension:Echo.
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

declare( strict_types=1 );

namespace Reverb;

use Reverb\Notification\NotificationListService;

class ReverbInit {
	public static function registerExtension(): void {
		global $wgDefaultUserOptions,
			   $wgHiddenPrefs,
			   $wgEnableHydraFeatures,
			   $wgReverbNotifications,
			   $wgReverbEnableWatchlistHandling;

		if ( $wgEnableHydraFeatures ) {
			return;
		}

		foreach ( $wgReverbNotifications as $notification => $notificationData ) {
			$wgDefaultUserOptions[ NotificationListService::getPreferenceKey( $notification, 'email' ) ] =
				$notificationData['defaults']['email'] ?? false;
			$wgDefaultUserOptions[ NotificationListService::getPreferenceKey( $notification, 'web' ) ] =
				$notificationData['defaults']['web'] ?? true;
		}

		$wgDefaultUserOptions[ NotificationListService::getPreferenceKey( 'user-interest-email-user', 'email' ) ] = 0;
		$wgDefaultUserOptions[ NotificationListService::getPreferenceKey( 'user-interest-email-user', 'web' ) ] = 1;
		$wgHiddenPrefs[] = NotificationListService::getPreferenceKey( 'user-interest-email-user', 'email' );

		if ( $wgReverbEnableWatchlistHandling ) {
			$wgHiddenPrefs[] = 'enotifusertalkpages';
			$wgHiddenPrefs[] = 'enotifwatchlistpages';
		}
	}
}
