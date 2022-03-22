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

use Http\Adapter\Guzzle6\Client;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Hydrawiki\Reverb\Client\V1\ClientFactory;
use MediaWiki\MediaWikiServices;

return [
	'ReverbApiClient' => function ( MediaWikiServices $services ) {
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

			return ( new ClientFactory )->make( $httpClient, MessageFactoryDiscovery::find(), $endPoint, $apiKey );
		}

		return ( new ClientFactory )->make( HttpClientDiscovery::find(), MessageFactoryDiscovery::find(), $endPoint,
			$apiKey );
	},
];
