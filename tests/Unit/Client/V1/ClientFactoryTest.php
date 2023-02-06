<?php

declare( strict_types=1 );

namespace Reverb\tests\Client\V1;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;
use Reverb\Client\V1\Client;
use Reverb\Client\V1\ClientFactory;

class ClientFactoryTest extends TestCase {
	/**
	 * Tests that a Client is created by the Client Factory.
	 */
	public function testClientFactoryMakesClient(): void {
		$factory = new ClientFactory();

		$client = $factory->make(
			$this->createMock( HttpClient::class ),
			$this->createMock( MessageFactory::class ),
			'https://www.example.com/api/v1',
			'watkey'
		);

		$this->assertInstanceOf( Client::class, $client );
	}
}
