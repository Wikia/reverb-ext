<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Resources;

class Notification extends Resource
{
    /**
     * Resource type as per the API.
     *
     * @var string
     */
    protected $type = 'notifications';

    /**
     * Attributes provided by the API and default values.
     *
     * @var array
     */
    protected $attributes = [
        'type'          => null,
        'message'       => null,
        'created-at'    => null,
        'dismissed-at'  => null,
        'url'           => null,
        // Temporary workaround until the service provides these as relations
        // see: https://gitlab.com/hydrawiki/services/reverb/issues/3
        'agent-id'      => null,
        'origin-id'     => null,
    ];

    /**
     * Relationships to other Resources.
     *
     * @var array
     */
    protected $relationships = [
        'origin' => [Site::class, self::RELATIONSHIP_ONE],
        'agent'  => [User::class, self::RELATIONSHIP_ONE],
        'target' => [User::class, self::RELATIONSHIP_ONE],
    ];
}
