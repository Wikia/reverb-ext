<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Exceptions;

use LogicException;

class ClientResourceCall extends LogicException {
	/**
	 * The resource call has too many parameters.
	 *
	 * @param array $parameters
	 *
	 * @return \Reverb\Client\V1\Exceptions\ClientResourceCall
	 */
	public static function parameters( array $parameters ): self {
		return new static( 'Resource calls expect 1 parameter maximum, received ' . count( $parameters ) );
	}

	/**
	 * The object passed to the resource call is not a Resource.
	 *
	 * @return \Reverb\Client\V1\Exceptions\ClientResourceCall
	 */
	public static function type( object $object ): self {
		return new static( 'Resource call expects a Resource, received ' . get_class( $object ) );
	}
}
