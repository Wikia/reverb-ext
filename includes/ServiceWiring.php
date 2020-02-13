<?php
/**
 * Reverb
 * Service Wiring for Reverb
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Hydrawiki\Reverb\Client\V1\ClientFactory;
use MediaWiki\MediaWikiServices;

return [
	'ReverbApiClient' => function (MediaWikiServices $services) {
		$mainConfig = $services->getMainConfig();
		$endPoint = $mainConfig->get('ReverbApiEndPoint');
		$apiKey = $mainConfig->get('ReverbApiKey');

		return (new ClientFactory)->make(
			HttpClientDiscovery::find(),
			MessageFactoryDiscovery::find(),
			$endPoint,
			$apiKey
		);
	}
];
