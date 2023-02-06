<?php
/**
 * IdentifierTest
 *
 * @package Tests\Unit
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

namespace Reverb\tests\Identifier;

use Reverb\Identifier\Identifier;
use Reverb\Identifier\InvalidIdentifierException;
use Tests\TestCase;

class IdentifierTest extends TestCase {
	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setup();
	}

	/**
	 * Make sure type/what returned is 'site'.
	 *
	 * @covers Identifier::whatAmI
	 *
	 * @return void
	 */
	public function testTypeIsSite() {
		$identifier = Identifier::factory('hydra:site:asdfbe');

		$this->assertSame('site', $identifier->whatAmI());
	}

	/**
	 * Make sure type/what returned is 'user'.
	 *
	 * @covers Identifier::whatAmI
	 *
	 * @return void
	 */
	public function testTypeIsUser() {
		$identifier = Identifier::factory('hydra:user:124234532');

		$this->assertSame('user', $identifier->whatAmI());
	}

	/**
	 * Make sure that the unique returned from whoAmI() matches the input.
	 *
	 * @covers Identifier::whatAmI
	 *
	 * @return void
	 */
	public function testValidUniqueIdIsReturned() {
		$identifier = Identifier::factory('hydra:user:124234532');

		$this->assertSame('124234532', $identifier->whoAmI());
	}

	/**
	 * Make sure that the namespace returned from whereIsHome() matches the input.
	 *
	 * @covers Identifier::whereIsHome
	 *
	 * @return void
	 */
	public function testValidNamespaceIsReturned() {
		$identifier = Identifier::factory('hydra:user:124234532');

		$this->assertSame('hydra', $identifier->whereIsHome());
	}

	/**
	 * Test that the namespace is caught as being too long.
	 *
	 * @covers Identifier::__construct
	 *
	 * @return void
	 */
	public function testNamespaceTooLong() {
		$this->expectException(InvalidIdentifierException::class);
		$identifier = Identifier::factory('hydra' . str_repeat('a', 100) . ':user:124234532'); // @codingStandardsIgnoreLine
	}

	/**
	 * Test that the type is caught as being too long.
	 *
	 * @covers Identifier::splitIdentifier
	 *
	 * @return void
	 */
	public function testTypeTooLong() {
		$this->expectException(InvalidIdentifierException::class);
		$identifier = Identifier::factory('hydra:user' . str_repeat('user', 10) . ':124234532');
	}

	/**
	 * Test that the ID is caught as being too long.
	 *
	 * @covers Identifier::splitIdentifier
	 *
	 * @return void
	 */
	public function testIdTooLong() {
		$this->expectException(InvalidIdentifierException::class);
		$identifier = Identifier::factory('hydra:user:124234532' . str_repeat(1, 100));
	}

	/**
	 * Make sure a local identifier returns true from isLocal().
	 *
	 * @covers Identifier::splitIdentifier
	 *
	 * @return void
	 */
	public function testIdentifierIsLocal() {
		$this->mockGlobalConfig->shouldReceive('get')->with('ReverbNamespace')->andReturn('hydra');
		$identifier = Identifier::factory('hydra:user:124234532');

		$this->assertTrue($identifier->isLocal());
	}

	/**
	 * Make sure a foreign identifier returns false from isLocal().
	 *
	 * @covers Identifier::isLocal
	 *
	 * @return void
	 */
	public function testIdentifierIsForeign() {
		$this->mockGlobalConfig->shouldReceive('get')->with('ReverbNamespace')->andReturn('hydra');
		$identifier = Identifier::factory('fandom:user:124234532');

		$this->assertFalse($identifier->isLocal());
	}

	/**
	 * Make sure a local identifier returns true from isLocal().
	 *
	 * @covers Identifier::isLocal
	 *
	 * @return void
	 */
	public function testSiteIdentifierIsLocal() {
		$this->mockGlobalConfig->shouldReceive('get')->with('ReverbNamespace')->andReturn('hydra');
		$mockWfWikiID = $this->getPHPMock('Reverb\Identifier', 'wfWikiID');
		$mockWfWikiID->andReturn('lol_gamepedia_en');
		$identifier = Identifier::factory('hydra:site:lol_gamepedia_en');

		$this->assertTrue($identifier->isLocal());
	}

	/**
	 * Make sure a foreign identifier returns false from isLocal().
	 *
	 * @covers Identifier::isLocal
	 *
	 * @return void
	 */
	public function testSiteIdentifierIsForeign() {
		$this->mockGlobalConfig->shouldReceive('get')->with('ReverbNamespace')->andReturn('hydra');
		$this->mockMWService->shouldReceive('getMainConfig')->andReturn($this->mockGlobalConfig);
		$mockWfWikiID = $this->getPHPMock('Reverb\Identifier', 'wfWikiID');
		$mockWfWikiID->andReturn('lol_gamepedia_en');
		$identifier = Identifier::factory('fandom:site:lol_gamepedia_en');

		$this->assertFalse($identifier->isLocal());
	}
}
