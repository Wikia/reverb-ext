<?php

declare( strict_types=1 );

namespace Reverb\tests\Client\V1\Api;

use PHPUnit\Framework\TestCase;
use Reverb\Client\V1\Api\Document;
use Reverb\Client\V1\Api\JsonApiResponse;
use Reverb\Client\V1\Exceptions\DocumentMissing;

class JsonApiResponseTest extends TestCase {
	/**
	 * Tests that an index is successful if the status code is 200 and the
	 * document contains many primary resources.
	 */
	public function testIsSuccessfulIndex(): void {
		$document = $this->createMock( Document::class );
		$document->method( 'isMany' )->willReturn( true );

		$response = new JsonApiResponse( 200, $document );
		$this->assertTrue( $response->isSuccessfulIndex() );
	}

	/**
	 * Tests that a read is successful if the status codei s 200 and the
	 * document has one primary resource.
	 */
	public function testIsSuccessfulRead(): void {
		$document = $this->createMock( Document::class );
		$document->method( 'isOne' )->willReturn( true );

		$response = new JsonApiResponse( 200, $document );
		$this->assertTrue( $response->isSuccessfulRead() );
	}

	/**
	 * Tests that when a Document was not a part of the Response that an
	 * exception is thrown when trying to access the document.
	 */
	public function testExceptionWhenDocumentIsNotAvailable(): void {
		$response = new JsonApiResponse( 200, null );

		$this->expectException( DocumentMissing::class );

		$response->document();
	}
}
