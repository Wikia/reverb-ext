<?php
/**
 * Reverb
 * Service Wiring for Reverb
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

use Fandom\Includes\Util\UrlUtilityService;
use Fandom\WikiConfig\WikiVariablesDataService;
use Fandom\WikiDomain\WikiConfigDataService;
use MediaWiki\MediaWikiServices;
use Reverb\Fixer\NotificationUserNoteAssetsUrlFixer;
use Reverb\Identifier\IdentifierService;
use Reverb\Notification\NotificationBroadcastFactory;
use Reverb\Notification\NotificationClient;
use Reverb\Notification\NotificationEmail;
use Reverb\Notification\NotificationFactory;
use Reverb\Notification\NotificationListService;
use Reverb\Notification\NotificationService;

return [
	NotificationBroadcastFactory::class =>
		static function ( MediaWikiServices $services ): NotificationBroadcastFactory {
			return new NotificationBroadcastFactory(
				$services->getMainConfig(),
				$services->getService( NotificationListService::class ),
				$services->getService( IdentifierService::class ),
				$services->getService( NotificationEmail::class ),
				$services->getService( NotificationService::class )
			);
		},
	NotificationFactory::class => static function ( MediaWikiServices $services ): NotificationFactory {
		return new NotificationFactory(
			$services->getMainConfig(),
			$services->getService( NotificationListService::class ),
			$services->getService( UrlUtilityService::class ),
			$services->getService( WikiConfigDataService::class ),
			$services->getService( WikiVariablesDataService::class ),
			$services->getUserFactory(),
			$services->getService( IdentifierService::class )
		);
	},

	NotificationService::class => static function ( MediaWikiServices $services ): NotificationService {
		return new NotificationService(
			$services->getService( IdentifierService::class ),
			$services->getService( NotificationClient::class ),
			$services->getService( NotificationFactory::class )
		);
	},

	NotificationClient::class => static function ( MediaWikiServices $services ): NotificationClient {
		$config = $services->getMainConfig();
		$apiKey = $config->get( 'ReverbApiKey' );

		return new NotificationClient(
			$services->getService( SERVICE_HTTP_CLIENT ),
			$config->get( 'ReverbApiEndPoint' ),
			[
				'Accept' => 'application/vnd.api+json',
				'Content-Type' => 'application/vnd.api+json',
				'Authorization' => $apiKey,
			]
		);
	},

	NotificationUserNoteAssetsUrlFixer::class =>
		static function ( MediaWikiServices $services ): NotificationUserNoteAssetsUrlFixer {
			return new NotificationUserNoteAssetsUrlFixer( $services->getMainConfig()->get( 'Server' ) );
		},

	NotificationEmail::class => static function ( MediaWikiServices $services ): NotificationEmail {
		return new NotificationEmail(
			$services->getUserOptionsLookup(),
			$services->getMainConfig(),
			$services->getService( NotificationListService::class ),
			$services->getService( NotificationFactory::class ),
			$services->getService( 'TwiggyService' )
		);
	},
	NotificationListService::class => static function ( MediaWikiServices $services ): NotificationListService {
		return new NotificationListService(
			$services->getUserOptionsLookup(),
			$services->getMainConfig(),
			$services->getUserGroupManager()
		);
	},

	IdentifierService::class => static function ( MediaWikiServices $services ): IdentifierService {
		return new IdentifierService(
			(int)$services->getMainConfig()->get( 'CityId' ),
			$services->getMainConfig()->get( 'ReverbNamespace' )
		);
	},
];
