<?php

declare( strict_types=1 );

namespace Reverb\tests\Client\V1\Hydrators;

use PHPUnit\Framework\TestCase;
use Reverb\Client\V1\Exceptions\ResourceTypeUnmapped;
use Reverb\Client\V1\Hydrators\ResourceFactory;
use Reverb\Client\V1\Resources\Resource;

class ResourceFactoryTest extends TestCase {
	/**
	 * Tests that a Resource is created from a resource type.
	 */
	public function testResourceIsMadeFromType(): void {
		$resourceType = new class() extends Resource {
		};

		$factory = new ResourceFactory( [
			'examples' => get_class( $resourceType ),
		] );

		$resource = $factory->make( 'examples' );

		$this->assertInstanceOf( get_class( $resourceType ), $resource );
	}

	/**
	 * Tests that when a resource type has not been mapped to a Resource that an
	 * exception is thrown.
	 */
	public function testUnmappedResourceTypeThrowsException(): void {
		$factory = new ResourceFactory( [] );

		$this->expectException( ResourceTypeUnmapped::class );

		$factory->make( 'undefined' );
	}
}
