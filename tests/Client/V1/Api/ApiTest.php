<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\Tests\Unit\V1\Api;

use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Hydrawiki\Reverb\Client\V1\Api\Api;
use Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse;
use Hydrawiki\Reverb\Client\V1\Api\MessageFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiTest extends TestCase
{
    /**
     * Create a Mock response.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->response = $this->createMock(ResponseInterface::class);
        $this->response->method('getStatusCode')->willReturn(200);
        $this->response->method('getBody')->willReturn(null);
    }

    /**
     * Tests that a GET request is made.
     */
    public function testGetRequest(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $httpClient = $this->createMock(HttpClient::class);
        $messageFactory = $this->createMock(MessageFactory::class);

        $messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', 'resources')
            ->willReturn($request);

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($this->response);

        $api = new Api($httpClient, $messageFactory);
        $response = $api->get('resources');

        $this->assertInstanceOf(JsonApiResponse::class, $response);
    }

    /**
     * Tests that a POST request with a body is made.
     */
    public function testPostRequestWithBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $httpClient = $this->createMock(HttpClient::class);
        $messageFactory = $this->createMock(MessageFactory::class);

        $messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('POST', 'resources', '{"x":"y"}')
            ->willReturn($request);

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($this->response);

        $api = new Api($httpClient, $messageFactory);
        $response = $api->post('resources', ['x' => 'y']);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
    }

    /**
     * Tests that a PATCH request with a body is made.
     */
    public function testPatchRequestWithBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $httpClient = $this->createMock(HttpClient::class);
        $messageFactory = $this->createMock(MessageFactory::class);

        $messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('PATCH', 'resources', '{"x":"y"}')
            ->willReturn($request);

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($this->response);

        $api = new Api($httpClient, $messageFactory);
        $response = $api->patch('resources', ['x' => 'y']);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
    }

    /**
     * Tests that a DELETE requet is made without a body.
     */
    public function testDeleteRequestWithoutBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $httpClient = $this->createMock(HttpClient::class);
        $messageFactory = $this->createMock(MessageFactory::class);

        $messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('DELETE', 'resources/1')
            ->willReturn($request);

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($this->response);

        $api = new Api($httpClient, $messageFactory);

        $this->assertInstanceOf(JsonApiResponse::class, $api->delete('resources/1'));
    }

    /**
     * Tests that a DELETE request is made with a body.
     */
    public function testDeleteRequestWithBody(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $httpClient = $this->createMock(HttpClient::class);
        $messageFactory = $this->createMock(MessageFactory::class);

        $messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('DELETE', 'resources/1/relationships/relations', '{"x":"y"}')
            ->willReturn($request);

        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($this->response);

        $api = new Api($httpClient, $messageFactory);
        $response = $api->delete('resources/1/relationships/relations', ['x' => 'y']);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
    }
}
