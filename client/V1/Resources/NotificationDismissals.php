<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Resources;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;

class NotificationDismissals extends Resource
{
    /**
     * Resource type as per the API.
     *
     * @var string
     */
    protected $type = 'notification-dismissals';

    /**
     * Attributes provided by the API and default values.
     *
     * @var array
     */
    protected $attributes = [
        'target-id'     => null
    ];

    /**
     * Relationships to other Resources.
     *
     * @var array
     */
    protected $relationships = [
        'target' => [User::class, self::RELATIONSHIP_ONE]
    ];
}
