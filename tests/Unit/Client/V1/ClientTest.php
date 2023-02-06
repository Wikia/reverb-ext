<?php

declare( strict_types=1 );

namespace Reverb\tests\Client\V1;

use PHPUnit\Framework\TestCase;
use Reverb\Client\Tests\Unit\V1\Mocks\MockResource;
use Reverb\Client\V1\Api\Api;
use Reverb\Client\V1\Api\Document;
use Reverb\Client\V1\Api\JsonApiResponse;
use Reverb\Client\V1\Client;
use Reverb\Client\V1\Collections\Resources;
use Reverb\Client\V1\Exceptions\ApiRequestUnsuccessful;
use Reverb\Client\V1\Exceptions\ClientResourceCall;
use Reverb\Client\V1\Hydrators\Hydrator;
use Reverb\Client\V1\Resources\Resource;

class ClientTest extends TestCase {
	/**
	 * Tests that when a resource call is made with multiple parameters an
	 * exception is thrown.
	 */
	public function testTooManyResourceParametersThrowsException(): void {
		$client = new Client( $this->createMock( Api::class ), $this->createMock( Hydrator::class ) );

		$this->expectException( ClientResourceCall::class );

		$client->resources( 1, 2 );
	}

	/**
	 * Tests that when a resource call is made with a non-Resource object that
	 * an exception is thrown.
	 */
	public function testNonResourceObjectThrowsException(): void {
		$client = new Client( $this->createMock( Api::class ), $this->createMock( Hydrator::class ) );

		$this->expectException( ClientResourceCall::class );

		$client->resources( $client );
	}

	/**
	 * Tests that the client retrieves an index of Resources.
	 */
	public function testClientIndexesResources(): void {
		$document = $this->createMock( Document::class );

		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $document );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->expects( $this->once() )
			->method( 'hydrate' )
			->with( $document )
			->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );
		$client->resources()->all();
	}

	/**
	 * Tests that when an Index is unsuccessful that an Exception is thrown.
	 */
	public function testUnsuccessfulIndexThrowsException(): void {
		$document = $this->createMock( Document::class );

		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( false );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources' )
			->willReturn( $response );

		$client = new Client( $api, $this->createMock( Hydrator::class ) );

		$this->expectException( ApiRequestUnsuccessful::class );

		$client->resources()->all();
	}

	/**
	 * Tests that nested resources can be accessed through a fluent interface.
	 */
	public function testNestedRelationshipRequests(): void {
		$document = $this->createMock( Document::class );

		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $document );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->expects( $this->once() )
			->method( 'hydrate' )
			->with( $document )
			->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources/1/children' )
			->willReturn( $response );

		$resource = $this->createMock( Resource::class );
		$resource->method( 'id' )->willReturn( '1' );

		$client = new Client( $api, $hydrator );
		$client->resources( $resource )->children()->all();
	}

	/**
	 * Tests that a single resource is retrieved by its ID.
	 */
	public function testClientRequestsSingleResource(): void {
		$document = $this->createMock( Document::class );

		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulRead' )->willReturn( true );
		$response->method( 'document' )->willReturn( $document );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->expects( $this->once() )
			->method( 'hydrate' )
			->with( $document )
			->willReturn(
				new class() extends Resource {
				}
			);

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources/1' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );
		$client->resources()->find( '1' );
	}

	/**
	 * Tests that the client's state is reset between requests.
	 */
	public function testSubsequentRequestsHaveFreshState(): void {
		$document = $this->createMock( Document::class );

		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $document );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->method( 'hydrate' )->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->with( 'resources' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );

		$client->resources()->all();
		$client->resources()->all();
	}

	/**
	 * Tests that one or more resources can be included using the client.
	 */
	public function testClientIncludes(): void {
		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $this->createMock( Document::class ) );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->method( 'hydrate' )->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources?include=relation1%2Crelation2' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );

		$client->resources()->include( 'relation1', 'relation2' )->all();
	}

	/**
	 * Tests that a request is made to the JSON:API to create a new Resource.
	 */
	public function testClientCreatesResource(): void {
		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulCreate' )->willReturn( true );
		$response->method( 'document' )->willReturn( $this->createMock( Document::class ) );

		$data = [
			'data' => [
				'type' => 'mocks',
				'attributes' => [
					'name' => 'John Doe',
				],
			],
		];

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'post' )
			->with( 'resources', $data )
			->willReturn( $response );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->expects( $this->once() )
			->method( 'hydrate' )
			->willReturn(
				new class extends Resource {
				}
			);

		$resource = new MockResource( [
			'name' => 'John Doe',
		] );

		$client = new Client( $api, $hydrator );
		$client->resources()->create( $resource );
	}

	/**
	 * Tests that when a resource is updated a request is made to the JSON:API
	 * service.
	 */
	public function testClientUpdatesResource(): void {
		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulUpdate' )->willReturn( true );

		$data = [
			'data' => [
				'type' => 'mocks',
				'id' => '1',
				'attributes' => [
					'name' => 'John Doe',
				],
			],
		];

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'patch' )
			->with( 'mocks/1', $data )
			->willReturn( $response );

		$hydrator = $this->createMock( Hydrator::class );

		$resource = new MockResource( [
			'name' => 'John Doe',
		] );
		$resource->setId( '1' );

		$client = new Client( $api, $hydrator );
		$result = $client->update( $resource );

		$this->assertSame( $result, $resource );
	}

	/**
	 * Tests that page[limit] and page[offset] are included when `page()` is
	 * used.
	 */
	public function testRequestIsPaginated(): void {
		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $this->createMock( Document::class ) );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->method( 'hydrate' )->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources?page%5Blimit%5D=10&page%5Boffset%5D=100' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );

		$client->resources()->page( 10, 100 )->all();
	}

	/**
	 * Tests that filter parameters are included when filtering.
	 */
	public function testRequestIsFiltered(): void {
		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $this->createMock( Document::class ) );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->method( 'hydrate' )->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'resources?filter%5Ba%5D=1&filter%5Bb%5D=2' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );

		$client->resources()->filter( [ 'a' => '1', 'b' => '2' ] )->all();
	}

	/**
	 * Tests that when a resource has a hyphenated name that it can be accessed
	 * using an underscore in place of any hyphens.
	 */
	public function testResourceNamesWithDashAreSupported(): void {
		$document = $this->createMock( Document::class );

		$response = $this->createMock( JsonApiResponse::class );
		$response->method( 'isSuccessfulIndex' )->willReturn( true );
		$response->method( 'document' )->willReturn( $document );

		$hydrator = $this->createMock( Hydrator::class );
		$hydrator->expects( $this->once() )
			->method( 'hydrate' )
			->with( $document )
			->willReturn( new Resources() );

		$api = $this->createMock( Api::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( 'example-resources' )
			->willReturn( $response );

		$client = new Client( $api, $hydrator );
		$client->example_resources()->all();
	}
}
