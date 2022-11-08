<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Exceptions;

use Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse;
use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use LogicException;

class ApiRequestUnsuccessful extends LogicException
{
    /**
     * API Response was not successful for an Index request.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse $response
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseFailed
     */
    public static function index(JsonApiResponse $response): self
    {
        return new static('Index resource request to API was not successful.');
    }

    /**
     * API Response was not successful for a Read request.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse $response
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseFailed
     */
    public static function read(JsonApiResponse $response): self
    {
        return new static('Read resource request to API was not successful.');
    }

    /**
     * API Response was not successful for a Create request.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse $response
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseFailed
     */
    public static function create(JsonApiResponse $response): self
    {
        return new static('Create resource request to API was not successful.');
    }

    /**
     * API Response was not successful for an Update request.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Api\JsonApiResponse $response
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseFailed
     */
    public static function update(JsonApiResponse $response): self
    {
        return new static('Update resource request to API was not successful.');
    }
}
