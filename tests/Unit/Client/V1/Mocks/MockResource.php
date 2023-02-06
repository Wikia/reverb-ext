<?php

declare(strict_types=1);

namespace Reverb\tests\Client\V1\Mocks;

use Reverb\Client\V1\Resources\Resource;

class MockResource extends Resource
{
    /**
     * Resource type as per the API.
     *
     * @var string
     */
    protected $type = 'mocks';

    /**
     * Attributes provided by the API and default values.
     *
     * @var array
     */
    protected $attributes = [
        'name' => null,
    ];

    /**
     * Relationships to other Resources.
     *
     * @var array
     */
    protected $relationships = [
        'mocks' => [MockResource::class, self::RELATIONSHIP_MANY],
        'mock'  => [MockResource::class, self::RELATIONSHIP_ONE],
    ];
}
