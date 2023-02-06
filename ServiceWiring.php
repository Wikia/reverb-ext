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

use Http\Adapter\Guzzle7\Client;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use MediaWiki\MediaWikiServices;
use Reverb\Client\V1\ClientFactory;
use Reverb\Fixer\NotificationUserNoteAssetsUrlFixer;

return [
	'ReverbApiClient' => static function ( MediaWikiServices $services ) {
		$mainConfig = $services->getMainConfig();
		$endPoint = $mainConfig->get( 'ReverbApiEndPoint' );
		$apiKey = $mainConfig->get( 'ReverbApiKey' );
		$envoySocketPath = $mainConfig->get( 'ReverbEnvoySocketPath' );

		// PLATFORM-5939: support calling Reverb through a local UDS
		if ( $envoySocketPath ) {
			$httpClient = Client::createWithConfig( [
				'curl' => [
					CURLOPT_UNIX_SOCKET_PATH => $envoySocketPath,
				],
			] );

			return ( new ClientFactory )->make(
				$httpClient,
				MessageFactoryDiscovery::find(),
				$endPoint,
				$apiKey
			);
		}

		return ( new ClientFactory )->make(
			HttpClientDiscovery::find(),
			MessageFactoryDiscovery::find(),
			$endPoint,
			$apiKey
		);
	},

	NotificationUserNoteAssetsUrlFixer::class => static function ( MediaWikiServices $services ) {
		return new NotificationUserNoteAssetsUrlFixer( $services->getMainConfig()->get( 'Server' ) );
	}
];
