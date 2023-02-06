<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Exceptions;

use RuntimeException;

class DocumentMissing extends RuntimeException {
	/**
	 * A Document is missing from the response.
	 *
	 * @return \Reverb\Client\V1\Exceptions\DocumentMissing
	 */
	public static function fromResponse(): self {
		return new static( 'Response does not contain a valid JSON:API Document.' );
	}
}
