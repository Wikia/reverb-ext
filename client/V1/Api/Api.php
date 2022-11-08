<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Api;

use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

class Api
{
    /**
     * HTTP Client repsonsible for making requests.
     *
     * @var \Http\Client\HttpClient
     */
    protected $client;

    /**
     * Factory responsible for creating Requests and Responses.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Api\MessageFactory
     */
    protected $messageFactory;

    /**
     * Constructs a new API instance.
     *
     * @param \Http\Client\HttpClient                        $client
     * @param \Hydrawiki\Reverb\Client\V1\Api\MessageFactory $messageFactory
     */
    public function __construct(
        HttpClient $client,
        MessageFactory $messageFactory
    ) {
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $path
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse
     */
    public function get(string $path): JsonApiResponse
    {
        $request = $this->messageFactory->createRequest('GET', $path);

        return $this->sendRequest($request);
    }

    /**
     * Make a POST request to the API with data.
     *
     * @param string $path
     * @param array  $data
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse
     */
    public function post(string $path, array $data): JsonApiResponse
    {
        $request = $this->messageFactory->createRequest('POST', $path, json_encode($data));

        return $this->sendRequest($request);
    }

    /**
     * Make a PATCH request to the API with data.
     *
     * @param string $path
     * @param array  $data
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse
     */
    public function patch(string $path, array $data): JsonApiResponse
    {
        $request = $this->messageFactory->createRequest('PATCH', $path, json_encode($data));

        return $this->sendRequest($request);
    }

    /**
     * Make a DELETE request to the API with optional data.
     *
     * @param string     $path
     * @param array|null $data
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse
     */
    public function delete(string $path, ?array $data = null): JsonApiResponse
    {
        $body = is_array($data) ? json_encode($data) : null;

        $request = $this->messageFactory->createRequest('DELETE', $path, $body);

        return $this->sendRequest($request);
    }

    /**
     * Send the request and return a JSON API Response.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     *
     * @return \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse
     */
    protected function sendRequest(RequestInterface $request): JsonApiResponse
    {
        $response = $this->client->sendRequest($request);

        return $this->messageFactory->createResponse(
            $response->getStatusCode(),
            (string) $response->getBody()
        );
    }
}
