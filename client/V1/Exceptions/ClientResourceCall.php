<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Exceptions;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use LogicException;

class ClientResourceCall extends LogicException
{
    /**
     * The resource call has too many parameters.
     *
     * @param array $parameters
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ClientResourceCall
     */
    public static function parameters(array $parameters): self
    {
        return new static('Resource calls expect 1 parameter maximum, received '.count($parameters));
    }

    /**
     * The object passed to the resource call is not a Resource.
     *
     * @param object $object
     *
     * @return \Hydrawiki\Reverb\Client\V1\Exceptions\ClientResourceCall
     */
    public static function type(Object $object): self
    {
        return new static('Resource call expects a Resource, received '.get_class($object));
    }
}
