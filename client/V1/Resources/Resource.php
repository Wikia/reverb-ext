<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Resources;

use Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAlreadyPopulated;
use Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAttributeUndefined;
use Hydrawiki\Reverb\Client\V1\Exceptions\ResourceRelationshipUndefined;
use Tightenco\Collect\Support\Collection;

abstract class Resource
{
    /**
     * Relationship for a single Resource.
     *
     * @var string
     */
    const RELATIONSHIP_ONE = 'toOne';

    /**
     * Relationship for many Resources.
     *
     * @var string
     */
    const RELATIONSHIP_MANY = 'toMany';

    /**
     * Resource type as per the API.
     *
     * @var string
     */
    protected $type;

    /**
     * Unique identifier for this Resource Object.
     *
     * @var string
     */
    protected $id;

    /**
     * Whitelist of attributes and their default values.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Meta data associated with the Resource.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Attribute's values.
     *
     * @var array
     */
    protected $values = [];

    /**
     * Relationships to other Resources.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Resources that belong to relationships.
     *
     * @var \Tightenco\Collect\Support\Collection
     */
    protected $relations = [];

    /**
     * Constructs a new Resource with optional attribute values.
     *
     * @param array|null $values
     */
    public function __construct(?array $values = null)
    {
        if (is_array($values)) {
            $this->addAttributeValues($values);
        }

        $this->relations = (new Collection($this->relationships))->map(function () {
            return new Collection;
        });
    }

    /**
     * Provides access to attributes as Resource properties.
     *
     * @param string $attribute
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAttributeUndefined
     *
     * @return mixed
     */
    public function __get(string $attribute)
    {
        $attribute = str_replace('_', '-', $attribute);

        if (!array_key_exists($attribute, $this->attributes)) {
            throw ResourceAttributeUndefined::attribute($this, $attribute);
        }

        return $this->values[$attribute] ?? $this->attributes[$attribute];
    }

    /**
     * Get the relation(s) belonging to a relationship.
     *
     * @param string $relationship
     * @param array  $parameters
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceRelationshipUndefined
     *
     * @return \Tightenco\Collect\Support\Collection|\Hydrawiki\Reverb\Client\V1\Resource|null
     */
    public function __call(string $relationship, array $parameters)
    {
        if (!array_key_exists($relationship, $this->relationships)) {
            throw ResourceRelationshipUndefined::relationship($this, $relationship);
        }

        list($resource, $type) = $this->relationships[$relationship];

        return $this->{"get{$type}Relationship"}($relationship);
    }

    /**
     * Get the Resource's ID.
     *
     * @return string|null
     */
    public function id(): ?string
    {
        return $this->id;
    }

    /**
     * Get the Resource's type.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Update attributes -- without persisting.
     *
     * @var array
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource
     */
    public function update(array $values): self
    {
        $this->addAttributeValues($values);

        return $this;
    }

    /**
     * Get all attributes of the Resource, using default values if no value
     * is set.
     *
     * @return array
     */
    public function attributes(): array
    {
        return array_merge($this->attributes, $this->values);
    }

    /**
     * Get all changed attributes and their new values.
     *
     * @return array
     */
    public function changes(): array
    {
        return $this->values;
    }

    /**
     * Get the Resource's metadata.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function meta(?string $key = null)
    {
        return $key ? $this->meta[$key] : $this->meta;
    }

    /**
     * Set the ID of the Resource.
     *
     * @param string $id
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAlreadyPopulated
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource
     */
    public function setId(string $id): self
    {
        if (!is_null($this->id)) {
            throw ResourceAlreadyPopulated::resource($this);
        }

        $this->id = $id;

        return $this;
    }

    /**
     * Set the attributes of the Resource at the time of initialisation.
     *
     * @param array $values
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAlreadyPopulated
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource
     */
    public function setAttributes(array $values): self
    {
        if (!empty($this->values)) {
            throw ResourceAlreadyPopulated::resource($this);
        }

        $this->addAttributeValues($values);

        return $this;
    }

    /**
     * Set the relations of the Resource at the time of initialisation.
     *
     * @param array $relations
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource
     */
    public function setRelations(array $relations): self
    {
        $this->relations = $this->relations
        ->filter(function ($relations, $relationship) {
            return array_key_exists($relationship, $this->relationships);
        })->map(function ($collection, $relationship) use ($relations) {
            return $collection->wrap($relations[$relationship] ?? null);
        });

        return $this;
    }

    /**
     * Add a Relation to a Relationship.
     *
     * @param string $relationship
     * @param \Hydrawiki\Reverb\Client\V1\Resource ...$resources
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ResourceRelationshipUndefined
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource
     */
    public function add(string $relationship, Resource ...$resources): self
    {
        if (!array_key_exists($relationship, $this->relationships)) {
            throw ResourceRelationshipUndefined::relationship($this, $relationship);
        }

        (new Collection($resources))->each(function ($resource) use ($relationship) {
            $this->relations->get($relationship)->push($resource);
        });

        return $this;
    }

    /**
     * Set the Resource's metadata.
     *
     * @param array $meta
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get the Resource attributes and relationships in a JSON:API data format.
     *
     * @return array
     */
    public function toData(): array
    {
        return [
            'data' => array_filter([
                'type' => $this->type,
                'id' => $this->id,
                'attributes' => $this->attributes(),
                'relationships' => $this->getFilledRelationships(),
            ]),
        ];
    }

    /**
     * Get relationships that are filled, in JSON:API data format.
     *
     * @return array
     */
    protected function getFilledRelationships(): array
    {
        return $this->relations->map(function ($resources) {
            return $resources->reject(function ($resource) {
                return is_null($resource->id());
            })->map(function ($resource) {
                return ['data' => [
                    'id' => $resource->id(),
                    'type' => $resource->type(),
                ]];
            });
        })->reject(function ($resources) {
            return $resources->isEmpty();
        })->map(function ($resources, $relationship) {
            [$resource, $type] = $this->relationships[$relationship];

            return $type === 'toOne' ? $resources->first() : $resources;
        })->toArray();
    }

    /**
     * Get the relation for a toOne relationship.
     *
     * @param string $relationship
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resource|null
     */
    protected function getToOneRelationship(string $relationship): ?self
    {
        return $this->relations[$relationship]->first();
    }

    /**
     * Get the relations for a toMany relationship.
     *
     * @param string $relationship
     *
     * @return \Tightenco\Collect\Support\Collection
     */
    protected function getToManyRelationship(string $relationship): Collection
    {
        return $this->relations[$relationship];
    }

    /**
     * Merge values into the attribute values.
     *
     * @param array $values
     *
     * @return array
     */
    protected function addAttributeValues(array $values): void
    {
        $this->values = array_merge(
            $this->values,
            array_intersect_key($values, $this->attributes)
        );
    }
}
