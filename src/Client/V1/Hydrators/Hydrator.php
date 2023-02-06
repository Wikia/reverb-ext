<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Hydrators;

use Reverb\Client\V1\Api\Document;

interface Hydrator {
	/**
	 * Hydrates a Document by turning Resource Objects into entities with their
	 * attributes and relations. Returns either a single primary resource or a
	 * Collection of primary resources depending on the Document type.
	 *
	 * @param \Reverb\Client\V1\Api\Document $document
	 *
	 * @return \Tightenco\Collect\Support\Collection|\Reverb\Client\V1\Resources\Resource
	 */
	public function hydrate( Document $document );
}