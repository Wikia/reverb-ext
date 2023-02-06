<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Api;

use Tightenco\Collect\Support\Collection;
use WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObject as YangResourceObject;

class ResourceObject
{
    /**
     * Yang Resource Object.
     *
     * @var \WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObject
     */
    protected $resourceObject;

    /**
     * Constructs a Resource Object wrapper around a Yang Resource Object.
     *
     * @param \WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObject $resourceObject
     */
    public function __construct(YangResourceObject $resourceObject)
    {
        $this->resourceObject = $resourceObject;
    }

    /**
     * Get a unique key for the object.
     *
     * @return string
     */
    public function key(): string
    {
        return "{$this->type()}.{$this->id()}";
    }

    /**
     * Get the type of object.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->resourceObject->type();
    }

    /**
     * Get the unique ID of the object.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->resourceObject->id();
    }

    /**
     * Get the attributes of the object.
     *
     * @return array
     */
    public function attributes(): array
    {
        return $this->resourceObject->attributes();
    }

    /**
     * Get the metadata of the object.
     *
     * @return array
     */
    public function meta(): array
    {
        return $this->resourceObject->meta();
    }

    /**
     * Get the object's relations in a relationship => [[type, id], [type, id]]
     * set.
     *
     * @return \Tightenco\Collect\Support\Collection
     */
    public function relations(): Collection
    {
        return (new Collection($this->resourceObject->relationships()))
            ->mapWithKeys(function ($relationship) {
                return [$relationship->name() => $relationship->resourceLinks()];
            });
    }
}
