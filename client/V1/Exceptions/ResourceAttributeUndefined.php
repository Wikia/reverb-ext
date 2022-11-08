<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Exceptions;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use LogicException;

class ResourceAttributeUndefined extends LogicException
{
    /**
     * The attribute has not been defined as part of the Resource.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Resources\Resource $resource
     * @param string                                   $attribute
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAttributeUndefined
     */
    public static function attribute(Resource $resource, string $attribute): self
    {
        return new static("Attribute {$attribute} does not exist on Resource ".get_class($resource));
    }
}
