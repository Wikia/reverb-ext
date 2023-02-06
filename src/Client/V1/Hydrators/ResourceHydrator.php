<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Hydrators;

use Reverb\Client\V1\Api\Document;
use Reverb\Client\V1\Api\ResourceObject;
use Tightenco\Collect\Support\Collection;

class ResourceHydrator implements Hydrator
{
    /**
     * Resource Factory.
     *
     * @var \Reverb\Client\V1\Resources\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * Constructs a new Resource Hydrator from a Document.
     *
     * @param \Reverb\Client\V1\Resources\ResourceFactory $resourceFactory
     */
    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Hydrates a Document by turning Resource Objects into Resources with their
     * attributes and relations. Returns either a single primary resource or a
     * Collection of primary resources depending on the Document type.
     *
     * @param \Reverb\Client\V1\Api\Document $document
     *
     * @return \Tightenco\Collect\Support\Collection|\Reverb\Client\V1\Resources\Resource
     */
    public function hydrate(Document $document)
    {
        $resources = $document->allResources()->mapWithKeys(function ($object) {
            return [$object->key() => $this->resourceFactory->make($object->type())];
        });

        $document->allResources()->each(function ($object) use ($resources) {
            $resources
                ->get($object->key())
                ->setId($object->id())
                ->setAttributes($object->attributes())
                ->setMeta($object->meta())
                ->setRelations($this->hydrateRelations($object, $resources));
        });

        $primary = $document->primaryResources()->map(function ($primary) use ($resources) {
            return $resources->get($primary->key());
        });

        return $document->isOne() ? $primary->first() : $primary;
    }

    /**
     * Hydrates relations on a Resource, turning 'relationship' => [[type, id]]
     * into 'relationship' => [Resource, Resource, Resource].
     *
     * @param \Reverb\Client\V1\Resources\ResourceObject $object
     * @param \Tightenco\Collect\Support\Collection          $resources
     *
     * @return array
     */
    protected function hydrateRelations(ResourceObject $object, Collection $resources): array
    {
        return $object->relations()->map(function ($relations, $relationship) {
            return (new Collection($relations))->map(function ($relation) use ($relationship) {
                return $relation + [
                    'relationship' => $relationship,
                    'key'          => "{$relation['type']}.{$relation['id']}",
                ];
            });
        })
        ->flatten(1)
        ->mapToGroups(function ($relation) use ($resources) {
            return [$relation['relationship'] => $resources->get($relation['key'])];
        })
        ->toArray();
    }
}
