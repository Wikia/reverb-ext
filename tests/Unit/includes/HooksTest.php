<?php
/**
 * Example Test
 *
 * @package Tests\Unit
 * @author  Samuel Hilson
 * @license GPL-2.0-or-later
 */

namespace Tests\Unit\Includes;

use Reverb\Hooks;
use Tests\TestCase;

class HooksTest extends TestCase {
	/**
	 * Create a new Hooks Instance
	 *
	 * @return Hooks
	 */
	protected function createHooks() {
		$hooks = new Hooks();
		return $hooks;
	}

	/**
	 * Test that Hooks can be initialized
	 *
	 * @covers Hooks::_construct
	 *
	 * @return void
	 */
	public function testNewHooks() {
		$hooks = $this->createHooks();
		$this->assertTrue($hooks instanceof Hooks);
	}

	/**
	 * Test onPageContentSaveComplete
	 *
	 * @covers Hooks::onPageContentSaveComplete
	 *
	 * @return void
	 */
	public function testOnPageContentSaveCompleteGood() {
		define('NS_USER_TALK', 3);
		$hooks = $this->createHooks();
		$mockWikiPage = $this->getMock('WikiPage');
		$mockUser = $this->getOverloadMock('User');
		$mockContent = $this->getMock('Content');
		$mockRevision = $this->getOverloadMock('Revision');
		$mockStatus = $this->getMock('Status');
		$mockTitle = $this->getMock('Title');
		$flag = 1;

		$mockStatus->shouldReceive('isGood')->andReturn(true);
		$mockWikiPage->shouldReceive('getTitle')->andReturn($mockTitle);

		$mockTitle->shouldReceive('getNamespace')->andReturn(NS_USER_TALK);
		$mockTitle->shouldReceive('getText')->andReturn('UserName');

		$mockUser->shouldReceive('newFromName')->andReturn($mockUser);
		$mockUser->shouldReceive('getId')->andReturn(1);

		$mockRevision->shouldReceive('isMinor')->andReturn(true);
		$mockUser->shouldReceive('isAllowed')->with('nominornewtalk')->andReturn(false);

		$hooks->onPageContentSaveComplete(
			$mockWikiPage,
			$mockUser,
			$mockContent,
			'',
			true,
			true,
			'',
			$flag,
			$mockRevision,
			$mockStatus,
			1,
			0
		);
	}

	/**
	 * Test onPageContentSaveComplete with a bad status
	 *
	 * @covers Hooks::onPageContentSaveComplete
	 *
	 * @return void
	 */
	public function testOnPageContentSaveCompleteBadStatus() {
		$hooks = $this->createHooks();
		$mockWikiPage = $this->getMock('WikiPage');
		$mockUser = $this->getOverloadMock('User');
		$mockContent = $this->getMock('Content');
		$mockRevision = $this->getOverloadMock('Revision');
		$mockStatus = $this->getMock('Status');
		$flag = 1;

		$mockStatus->shouldReceive('isGood')->andReturn(false);

		$result = $hooks->onPageContentSaveComplete(
			$mockWikiPage,
			$mockUser,
			$mockContent,
			'',
			true,
			true,
			'',
			$flag,
			$mockRevision,
			$mockStatus,
			1,
			0
		);
		$this->assertTrue($result);
	}
}
