<?php

declare( strict_types=1 );

namespace Reverb\Client\V1\Api;

use Reverb\Client\V1\Collections\Resources;
use WoohooLabs\Yang\JsonApi\Schema\Document as YangDocument;

class Document {
	/**
	 * Yang Document.
	 *
	 * @var \WoohooLabs\Yang\JsonApi\Schema\Document
	 */
	protected $document;

	/**
	 * Constructs a new Document wrapper around a Yang Document.
	 *
	 * @param \WoohooLabs\Yang\JsonApi\Schema\Document $document
	 */
	public function __construct( YangDocument $document ) {
		$this->document = $document;
	}

	/**
	 * Get all (primary, included) resources that are in the Document.
	 *
	 * @return \Reverb\Client\V1\Collections\Resources
	 */
	public function allResources(): Resources {
		return $this->primaryResources()->merge( $this->includedResources() );
	}

	/**
	 * Get primary resources that are in the Document.
	 *
	 * @return \Reverb\Client\V1\Collections\Resources
	 */
	public function primaryResources(): Resources {
		$type = $this->isOne() ? 'primaryResource' : 'primaryResources';

		$resources = ( new Resources() )->wrap( $this->document->{$type}() );

		$resources = $this->wrapAsResourceObjects( $resources );

		$resources->setMeta( $this->document->meta() ?? [] );

		return $resources;
	}

	/**
	 * Get resources that were included in the document.
	 *
	 * @return \Reverb\Client\V1\Collections\Resources
	 */
	public function includedResources(): Resources {
		return $this->wrapAsResourceObjects( new Resources( $this->document->includedResources() ) );
	}

	/**
	 * Does the Document provide a single primary resource?
	 *
	 * @return bool
	 */
	public function isOne(): bool {
		return $this->document->isSingleResourceDocument();
	}

	/**
	 * Does the document provide many primary resources?
	 *
	 * @return bool
	 */
	public function isMany(): bool {
		return $this->document->isResourceCollectionDocument();
	}

	/**
	 * Wrap each Yang ResourceObject in our own ResourceObject class.
	 *
	 * @param \Reverb\Client\V1\Collections\Resources $objects
	 *
	 * @return \Reverb\Client\V1\Collections\Resources
	 */
	protected function wrapAsResourceObjects( Resources $objects ): Resources {
		return $objects->map( static function ( $object ) {
			return new ResourceObject( $object );
		} );
	}
}
