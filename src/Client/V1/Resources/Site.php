<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Resources;

class Site extends Resource {
	/**
	 * Resource type as per the API.
	 *
	 * @var string
	 */
	protected $type = 'sites';

	/**
	 * Attributes provided by the API and default values.
	 *
	 * @var array
	 */
	protected $attributes = [
		'domain' => null,
	];
}
