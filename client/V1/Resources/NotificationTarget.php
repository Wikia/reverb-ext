<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\V1\Resources;

use Hydrawiki\Reverb\Client\V1\Resources\Resource;

class NotificationTarget extends Resource
{
    /**
     * Resource type as per the API.
     *
     * @var string
     */
    protected $type = 'notification-targets';

    /**
     * Attributes provided by the API and default values.
     *
     * @var array
     */
    protected $attributes = [
        'created-at'     => null,
        'dismissed-at'   => null,
        // Temporary workaround until the service provides these as relations
        // see: https://gitlab.com/hydrawiki/services/reverb/issues/3
        'target-id'      => null,
    ];

    /**
     * Relationships to other Resources.
     *
     * @var array
     */
    protected $relationships = [
        'notification' => [Notification::class, self::RELATIONSHIP_ONE],
    ];
}
