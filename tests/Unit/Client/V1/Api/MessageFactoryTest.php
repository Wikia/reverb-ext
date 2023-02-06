<?php

declare(strict_types=1);

namespace Reverb\tests\Client\V1\Api;

use Http\Message\MessageFactory as HttpMessageFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Reverb\Client\V1\Api\Document;
use Reverb\Client\V1\Api\MessageFactory;
use Reverb\Client\V1\Exceptions\ApiResponseInvalid;

class MessageFactoryTest extends TestCase
{
    /**
     * Tests that the request is created with the method, URI (including
     * endpoint) and body.
     */
    public function testRequestHasAllValues(): void
    {
        $httpMessageFactory = $this->createMock(HttpMessageFactory::class);
        $httpMessageFactory->expects($this->once())
            ->method('createRequest')
            ->with(
                'POST',
                'https://www.example.com/api/v1/resources/1/relationships/relations',
                [
                    'Accept'       => 'application/vnd.api+json',
                    'Content-Type' => 'application/vnd.api+json',
                ],
                '{"x":"y"}'
            )
            ->willReturn($this->createMock(RequestInterface::class));

        $messageFactory = new MessageFactory(
            $httpMessageFactory,
            'https://www.example.com/api/v1'
        );

        $messageFactory->createRequest(
            'POST',
            'resources/1/relationships/relations',
            '{"x":"y"}'
        );
    }

    /**
     * Tests that a Response is created without a document when the body of the
     * response is empty.
     */
    public function testResponseCreatedWithoutDocument(): void
    {
        $messageFactory = new MessageFactory(
            $this->createMock(HttpMessageFactory::class),
            'https://www.example.com/api/v1'
        );

        $response = $messageFactory->createResponse(204, '');

        $this->assertEquals(204, $response->statusCode());
        $this->assertFalse($response->hasDocument());
    }

    /**
     * Tests that a Response is created with a Document when there is a body.
     */
    public function testResponseCreatedWithDocument(): void
    {
        $messageFactory = new MessageFactory(
            $this->createMock(HttpMessageFactory::class),
            'https://www.example.com/api/v1'
        );

        $response = $messageFactory->createResponse(200, '{"data":[]}');

        $this->assertInstanceOf(Document::class, $response->document());
    }

    /**
     * Tests that when the response contains a body but is not valid JSON that
     * an exception is thrown.
     */
    public function testResponseWithInvalidJsonThrowsException(): void
    {
        $messageFactory = new MessageFactory(
            $this->createMock(HttpMessageFactory::class),
            'https://www.example.com/api/v1'
        );

        $this->expectException(ApiResponseInvalid::class);

        $messageFactory->createResponse(200, 'invalid-json');
    }
}
