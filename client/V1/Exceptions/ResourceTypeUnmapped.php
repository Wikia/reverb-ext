<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Exceptions;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use LogicException;

class ResourceTypeUnmapped extends LogicException
{
    /**
     * The resource type has not been mapped to a Resource.
     *
     * @param string $type
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceTypeUnmapped
     */
    public static function type(string $type): self
    {
        return new static("Resource type {$type} has not been mapped to a Resource.");
    }
}
