<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Exceptions;

use LogicException;

class ResourceTypeUnmapped extends LogicException
{
    /**
     * The resource type has not been mapped to a Resource.
     *
     * @param string $type
     *
     * @return \Reverb\Client\V1\Exceptions\ResourceTypeUnmapped
     */
    public static function type(string $type): self
    {
        return new static("Resource type {$type} has not been mapped to a Resource.");
    }
}
