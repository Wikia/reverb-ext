<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Exceptions;

use LogicException;
use Reverb\Client\V1\Resources\Resource;

class ResourceAttributeUndefined extends LogicException
{
    /**
     * The attribute has not been defined as part of the Resource.
     *
     * @param \Reverb\Client\V1\Resources\Resource $resource
     * @param string $attribute
     *
     * @return \Reverb\Client\V1\Exceptions\ResourceAttributeUndefined
     */
    public static function attribute(Resource $resource, string $attribute): self
    {
        return new static("Attribute {$attribute} does not exist on Resource ".get_class($resource));
    }
}
