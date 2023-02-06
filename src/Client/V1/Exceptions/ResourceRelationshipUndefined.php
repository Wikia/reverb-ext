<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Exceptions;

use LogicException;
use Reverb\Client\V1\Resources\Resource;

class ResourceRelationshipUndefined extends LogicException {
	/**
	 * The relationship has not been defined as part of the Resource.
	 *
	 * @param \Reverb\Client\V1\Resources\Resource $resource
	 * @param string $relationship
	 *
	 * @return \Reverb\Client\V1\Exceptions\ResourceRelationshipUndefined
	 */
	public static function relationship( Resource $resource, string $relationship ): self {
		return new static( "Relationship {$relationship} does not exist on Resource " . get_class( $resource ) );
	}
}
