<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\Tests\Unit\V1;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Hydrawiki\Reverb\Client\V1\Client;
use Hydrawiki\Reverb\Client\V1\ClientFactory;
use PHPUnit\Framework\TestCase;

class ClientFactoryTest extends TestCase
{
    /**
     * Tests that a Client is created by the Client Factory.
     */
    public function testClientFactoryMakesClient(): void
    {
        $factory = new ClientFactory();

        $client = $factory->make(
            $this->createMock(HttpClient::class),
            $this->createMock(MessageFactory::class),
            'https://www.example.com/api/v1',
			'watkey'
        );

        $this->assertInstanceOf(Client::class, $client);
    }
}
