<?php

namespace Reverb\Identifier;

class IdentifierService {
	private const SITE = 'site';
	private const USER = 'user';

	public function __construct( private int $wikiId, private string $reverbNamespace ) {
	}

	public function forUser( string $id ): string {
		return $this->buildKey( self::USER, $id );
	}

	public function forLocalSite(): string {
		return $this->buildKey( self::SITE, $this->wikiId );
	}

	public function idFromKey( string $key ): ?string {
		$parts = explode( ':', $key );
		return $parts[2] ?? null;
	}

	private function buildKey( string $type, string $id ): string {
		return implode( ':', [ $this->reverbNamespace, $type, $id ] );
	}
}
