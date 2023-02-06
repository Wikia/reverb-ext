<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Exceptions;

use LogicException;
use Reverb\Client\V1\Resources\Resource;

class ResourceAlreadyPopulated extends LogicException {
	/**
	 * The Resource has already been populated with attributes.
	 *
	 * @param \Reverb\Client\V1\Resources\Resource $resource
	 *
	 * @return \Reverb\Client\V1\Exceptions\ResourceAlreadyPopulated
	 */
	public static function resource( Resource $resource ): self {
		return new static( 'Resource has already been populated, use `update()` to make changes.' );
	}
}
