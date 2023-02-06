<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Hydrators;

use Reverb\Client\V1\Exceptions\ResourceTypeUnmapped;
use Reverb\Client\V1\Resources\Resource;

class ResourceFactory
{
    /**
     * Resource Object type to Resource.
     *
     * @var array
     */
    protected $resourceMap;

    /**
     * Map of Resource Object types (from the API) to local Resources.
     *
     * @param array $resourceMap
     */
    public function __construct(array $resourceMap)
    {
        $this->resourceMap = $resourceMap;
    }

    /**
     * Make a Resource from a ResourceObject.
     *
     * @param string $type
     *
     * @return \Reverb\Client\V1\Resources\Resource
     *@throws \Reverb\Client\V1\Exceptions\ResourceTypeUnmapped
     *
     */
    public function make(string $type): Resource
    {
        if (!array_key_exists($type, $this->resourceMap)) {
            throw ResourceTypeUnmapped::type($type);
        }

        return new $this->resourceMap[$type]();
    }
}
