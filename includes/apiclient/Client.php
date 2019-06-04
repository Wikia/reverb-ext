<?php
/**
 * Reverb
 * Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\ApiClient

use Hydrawiki\Reverb\Client\V1\ClientFactory;

class Client {
	/**
	 * Reverb API End Point
	 *
	 * @var string
	 */
	private $apiEndPoint = 'https://reverb.localhost/v1/';

	/**
	 * Reverb API Client
	 *
	 * @var Hydrawiki\Reverb\Client\V1
	 */
	private $client = null;

	/**
	 * Main Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$endPoint = $config->get('ReverbApiEndPoint');
		if (!empty($endPoint)) {
			$this->apiEndPoint = $endPoint;
		}

		$this->client = (new ClientFactory)->make(
			HttpClientDiscovery::find(),
			MessageFactoryDiscovery::find(),
			$this->apiEndPoint
		);
	}

	/**
	 * Handle bubbling down calls to the client.
	 *
	 * @param string $name Function Name
	 * @param string $arguments Arguments passed to the function.
	 *
	 * @return mixed
	 */
	private function __call($name, $arguments) {
		if (!method_exists($this->client, $name)) {
			throw new Exception('Invalid method called.');
		}
		return call_user_func_array([$this->client, $name], $arguments);
	}
}
