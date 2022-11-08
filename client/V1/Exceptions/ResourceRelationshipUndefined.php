<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Exceptions;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use LogicException;

class ResourceRelationshipUndefined extends LogicException
{
    /**
     * The relationship has not been defined as part of the Resource.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Resources\Resource $resource
     * @param string                                   $relationship
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceRelationshipUndefined
     */
    public static function relationship(Resource $resource, string $relationship): self
    {
        return new static("Relationship {$relationship} does not exist on Resource ".get_class($resource));
    }
}
