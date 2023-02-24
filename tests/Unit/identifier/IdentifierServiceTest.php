<?php

namespace Reverb\tests\Identifier;

use PHPUnit\Framework\TestCase;
use Reverb\Identifier\IdentifierService;

class IdentifierServiceTest extends TestCase {
	private const WIKI_ID = 147;
	private const REVERB_NAMESPACE = 'hydra';
	private IdentifierService $sut;

	public function setUp(): void {
		parent::setup();
		$this->sut = new IdentifierService( self::WIKI_ID, self::REVERB_NAMESPACE );
	}

	public function testGetLocalSiteIdentifier(): void {
		$identifier = $this->sut->forLocalSite();
		$this->assertEquals( 'hydra:site:147', $identifier );
	}

	public function testGetUserIdentifier(): void {
		$identifier = $this->sut->forUser( '65645' );
		$this->assertEquals( 'hydra:user:65645', $identifier );
	}

	/**
	 * @dataProvider provideIsBadFile
	 * @return void
	 */
	public function testGetIdFromIdentifier( string $identifier, ?string $expected ): void {
		$id = $this->sut->idFromKey( $identifier );
		$this->assertEquals( $expected, $id );
	}

	public static function provideIsBadFile(): array {
		return [
			'user identifier' => [ 'hydra:user:65645', '65645' ],
			'site identifier' => [ 'hydra:site:321325', '321325' ],
			'composed user identifier' => [ 'hydra:user:321325:12312', '321325' ],
			'composed site identifier' => [ 'hydra:site:321325:12312', '321325' ],
			'invalid identifier' => [ 'asdasdasd', null ],
		];
	}
}
