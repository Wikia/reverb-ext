<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Resources;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;

class User extends Resource
{
    /**
     * Resource type as per the API.
     *
     * @var string
     */
    protected $type = 'users';

    /**
     * Attributes provided by the API and default values.
     *
     * @var array
     */
    protected $attributes = [
        'name'  => null,
        'email' => null,
    ];

    /**
     * Relationships to other Resources.
     *
     * @var array
     */
    protected $relationships = [
        'notifications' => [Notification::class, self::RELATIONSHIP_MANY],
    ];
}
