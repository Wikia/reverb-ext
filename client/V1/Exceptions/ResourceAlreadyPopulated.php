<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Exceptions;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use LogicException;

class ResourceAlreadyPopulated extends LogicException
{
    /**
     * The Resource has already been populated with attributes.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Resources\Resource $resource
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAlreadyPopulated
     */
    public static function resource(Resource $resource): self
    {
        return new static('Resource has already been populated, use `update()` to make changes.');
    }
}
