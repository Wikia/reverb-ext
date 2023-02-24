<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Api;

use Http\Message\MessageFactory as HttpMessageFactory;
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseInvalid;
use Psr\Http\Message\RequestInterface;
use WoohooLabs\Yang\JsonApi\Schema\Document as YangDocument;

class MessageFactory
{
    /**
     * HTTP Message Factory.
     *
     * @var \Http\Message\MessageFactory
     */
    protected $httpMessageFactory;

    /**
     * API Endpoint for all requests.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Headers included with the request.
     *
     * @var array
     */
    protected $headers;

    /**
     * Constructs a new Message Factory.
     *
     * @param \Http\Message\MessageFactory $httpMessageFactory
     * @param string                       $endpoint
     * @param array                        $headers
     */
    public function __construct(
        HttpMessageFactory $httpMessageFactory,
        string $endpoint,
        array $headers = []
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->endpoint = $endpoint;
        $this->headers = array_merge([
            'Accept'       => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ], $headers);
    }

    /**
     * Create a request through the Message Factory, using the headers and
     * API endpoint.
     *
     * @param string      $method
     * @param string      $path
     * @param string|null $body
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequest(
        string $method,
        string $path,
        ?string $body = null
    ): RequestInterface {
        return $this->httpMessageFactory->createRequest(
            $method,
            "{$this->endpoint}/{$path}",
            $this->headers,
            $body
        );
    }

    /**
     * Create a JsonApiResponse from a Response body.
     *
     * @param int         $statusCode
     * @param string|null $response
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseInvalid
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse
     */
    public function createResponse(int $statusCode = 200, ?string $response = null): JsonApiResponse
    {
        if (is_string($response) && $response !== '') {
            $properties = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ApiResponseInvalid::json($response);
            }

            $document = new Document(
                YangDocument::fromArray($properties)
            );
        }

        return new JsonApiResponse($statusCode, $document ?? null);
    }
}
