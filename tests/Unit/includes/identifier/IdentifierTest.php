<?php
/**
 * IdentifierTest
 *
 * @package Tests\Unit
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

namespace Tests\Unit\Includes\Identifier;

use Reverb\Identifier\Identifier;
use Tests\TestCase;

class IdentifierTest extends TestCase {
	/**
	 * Make sure type/what returned is 'site'.
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testTypeIsSite() {
		$identifier = Identifier::factory('hydra/site:asdfbe');

		$this->assertTrue($identifier->whatAmI() === 'site');
	}

	/**
	 * Make sure type/what returned is 'user'.
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testTypeIsUser() {
		$identifier = Identifier::factory('hydra/user:124234532');

		$this->assertTrue($identifier->whatAmI() === 'user');
	}

	/**
	 * Make sure that the unique returned from whoAmI() matches the input.
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testValidUniqueIdIsReturned() {
		$identifier = Identifier::factory('hydra/user:124234532');

		$this->assertTrue($identifier->whoAmI() === '124234532');
	}

	/**
	 * Make sure that the namespace returned from whereIsHome() matches the input.
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testValidNamespaceIsReturned() {
		$identifier = Identifier::factory('hydra/user:124234532');

		$this->assertTrue($identifier->whereIsHome() === 'hydra');
	}

	/**
	 * Make sure a local identifier returns true from isLocal().
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testIdentifierIsLocal() {
		// @TODO: Use Mockery to spin up a fake MediaWikiServices to return the configuration value for $wgReverbNamespace = 'hydra'.
		$identifier = Identifier::factory('hydra/user:124234532');

		$this->assertFalse($identifier->isLocal());
	}

	/**
	 * Make sure a foreign identifier returns false from isLocal().
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testIdentifierIsForeign() {
		// @TODO: Use Mockery to spin up a fake MediaWikiServices to return the configuration value for $wgReverbNamespace = 'hydra'.
		$identifier = Identifier::factory('fandom/user:124234532');

		$this->assertFalse($identifier->isLocal());
	}

	/**
	 * Make sure a local identifier returns true from isLocal().
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testSiteIdentifierIsLocal() {
		// @TODO: Use Mockery to spin up a fake MediaWikiServices to return the configuration value for $wgReverbNamespace = 'hydra'.
		// @TODO: Also mock wfWikiID() to return the correct unique ID.
		$identifier = Identifier::factory('hydra/site:lol_gamepedia_en');

		$this->assertFalse($identifier->isLocal());
	}

	/**
	 * Make sure a foreign identifier returns false from isLocal().
	 *
	 * @coversNothing
	 *
	 * @return void
	 */
	public function testSiteIdentifierIsForeign() {
		// @TODO: Use Mockery to spin up a fake MediaWikiServices to return the configuration value for $wgReverbNamespace = 'hydra'.
		// @TODO: Also mock wfWikiID() to return the "correct" unique ID.
		$identifier = Identifier::factory('fandom/site:lol_gamepedia_en');

		$this->assertFalse($identifier->isLocal());
	}
}
