<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1;

use Hydrawiki\Reverb\Client\V1\Api\Api;
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiRequestUnsuccessful;
use Hydrawiki\Reverb\Client\V1\Exceptions\ClientResourceCall;
use Hydrawiki\Reverb\Client\V1\Hydrators\Hydrator;
use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use Hydrawiki\Reverb\Client\V1\Collections\Resources;
use Tightenco\Collect\Support\Collection;

class Client
{
    /**
     * API Client responsible for making requests.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Api\Api
     */
    protected $api;

    /**
     * Hydrator responsible for transforming Document into one or many entities.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Hydrators\Hydrator
     */
    protected $hydrator;

    /**
     * Resources used to build the request path.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Collections\Resources
     */
    protected $resources;

    /**
     * Parameters to include as the request's query string.
     *
     * @var \Hydrawiki\Reverb\Client\V1\Collections\Resources
     */
    protected $parameters;

    /**
     * Constructs a new Client.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Api\Api      $api
     * @param \Hydrawiki\Reverb\Client\V1\Hydrators\Hydrator $hydrator
     */
    public function __construct(Api $api, Hydrator $hydrator)
    {
        $this->api = $api;
        $this->hydrator = $hydrator;
        $this->newCollectionState();
    }

    /**
     * Add a resource to the request path, e.g: `wikis()`.
     *
     * @param string $resource
     * @param array  $parameters
     *
     * @throws \Hydrawiki\Reverb\Client\V1\Exceptions\ClientResourceCall
     *
     * @return \Hydrawiki\Reverb\Client\V1\Client
     */
    public function __call(string $resource, array $parameters)
    {
        if (count($parameters) > 1) {
            throw ClientResourceCall::parameters($parameters);
        }

        $object = reset($parameters);

        if ($object && !$object instanceof Resource) {
            throw ClientResourceCall::type($object);
        }

        $this->resources->push(new Collection([
            'type'   => str_replace('_', '-', $resource),
            'object' => $object,
        ]));

        return $this;
    }

    /**
     * Retrieve an index of a Resource with all primary resources hydrated.
     *
     * @return \Hydrawiki\Reverb\Client\V1\Collections\Resources
     */
    public function all(): Resources
    {
        $response = $this->api->get($this->compilePath());

        if (!$response->isSuccessfulIndex()) {
            throw ApiRequestUnsuccessful::index($response);
        }

        return $this->hydrator->hydrate($response->document());
    }

    /**
     * Find a single Resource by its ID.
     *
     * @param string $id
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resources\Resource
     */
    public function find(string $id): Resource
    {
        $response = $this->api->get($this->compilePath($id));

        if (!$response->isSuccessfulRead()) {
            throw ApiRequestUnsuccessful::read($response);
        }

        return $this->hydrator->hydrate($response->document());
    }

    /**
     * Add one or more relations to be included.
     *
     * @param string ...$relations
     *
     * @return \Hydrawiki\Reverb\Client\V1\Client
     */
    public function include(string ...$relations): self
    {
        $this->parameters->put(
            'includes',
            $this->parameters->get('includes', []) + $relations
        );

        return $this;
    }

    /**
     * Add filters to the query.
     *
     * @param array $filters
     *
     * @return \Hydrawiki\Reverb\Client\V1\Client
     */
    public function filter(array $filters): self
    {
        $this->parameters->put(
            'filters',
            $this->parameters->get('filters', []) + $filters
        );

        return $this;
    }


    /**
     * Set the page (limit, offset) for the request.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return \Hydrawiki\Reverb\Client\V1\Client
     */
    public function page(int $limit, int $offset): self
    {
        $this->parameters->put('pagination', [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $this;
    }

    /**
     * Create a new Resource.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Resources\Resource $resource
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resources\Resource
     */
    public function create(Resource $resource): Resource
    {
        $response = $this->api->post(
            $this->compilePath(),
            $resource->toData()
        );

        if (! $response->isSuccessfulCreate()) {
            throw ApiRequestUnsuccessful::create($response);
        }

        return $this->hydrator->hydrate($response->document());
    }

    /**
     * Update a Resource. If the service returns a Document, hydrate a Resource
     * otherwise return the Resource as-is.
     *
     * @param \Hydrawiki\Reverb\Client\V1\Resources\Resource $resource
     *
     * @return \Hydrawiki\Reverb\Client\V1\Resources\Resource
     */
    public function update(Resource $resource): Resource
    {
        $response = $this->api->patch(
            "{$resource->type()}/{$resource->id()}",
            $resource->toData()
        );

        if (! $response->isSuccessfulUpdate()) {
            throw ApiRequestUnsuccessful::update($response);
        }

        if ($response->hasDocument()) {
            return $this->hydrator->hydrate($response->document());
        }

        return $resource;
    }

    /**
     * Compile the path for the request, resetting the client state.
     *
     * @param string|null $id
     *
     * @return string
     */
    protected function compilePath(?string $id = null): string
    {
        $path = $this->resources
            ->map(function ($resource) {
                return [
                    $resource->get('type'),
                    $resource->get('object') ? $resource->get('object')->id() : null,
                ];
            })
            ->flatten()
            ->merge($id)
            ->filter()
            ->implode('/');

        $query = $this->queryString();

        $this->newCollectionState();

        return $path.($query ? "?{$query}" : '');
    }

    /**
     * Build the query string for the request.
     *
     * @return string
     */
    protected function queryString(): string
    {
        return http_build_query(array_merge(
            $this->includes(),
            $this->pagination(),
            $this->filters()
        ));
    }

    /**
     * Get the list of relations to include in the request.
     *
     * @return array
     */
    protected function includes(): array
    {
        $includes = $this->parameters->get('includes', []);

        return $includes ? ['include' => implode(',', $includes)] : [];
    }

    /**
     * Get the filters to include in the request.
     *
     * @return array
     */
    protected function filters(): array
    {
        $filters = $this->parameters->get('filters', []);

        return $filters ? ['filter' => $filters] : [];
    }

    /**
     * Get the pagination parameters to include in the request.
     *
     * @return array
     */
    protected function pagination(): array
    {
        $pagination = $this->parameters->get('pagination', []);

        return $pagination ? ['page' => $pagination] : [];
    }

    /**
     * Reset the state of the collections used by the client.
     *
     * @return void
     */
    protected function newCollectionState(): void
    {
        $this->resources = new Collection();
        $this->parameters = new Collection();
    }
}
