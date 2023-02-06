<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Exceptions;

use RuntimeException;

class ApiResponseInvalid extends RuntimeException
{
    /**
     * API response is not valid JSON.
     *
     * @param string $response
     *
     * @return \Reverb\Client\V1\Exceptions\ApiResponseInvalid
     */
    public static function json(string $response): self
    {
        return new static("Response body `{$response}` is not valid JSON.");
    }
}
