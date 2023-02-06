<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Resources;

class NotificationBroadcast extends Resource {
	/**
	 * Resource type as per the API.
	 *
	 * @var string
	 */
	protected $type = 'notification-broadcasts';

	/**
	 * Attributes provided by the API and default values.
	 *
	 * @var array
	 */
	protected $attributes = [
		'type'        => null,
		'message'     => null,
		'created-at'  => null,
		'url'         => null,
		// Temporary workaround until the service accepts these as relations
		// see: https://gitlab.com/hydrawiki/services/reverb/issues/3
		'origin-id'   => null,
		'agent-id'    => null,
		'target-ids'  => null
	];

	/**
	 * Relationships to other Resources.
	 *
	 * @var array
	 */
	protected $relationships = [
		'origin'  => [ Site::class, self::RELATIONSHIP_ONE ],
		'agent'   => [ User::class, self::RELATIONSHIP_ONE ],
		'targets' => [ User::class, self::RELATIONSHIP_MANY ],
	];
}
