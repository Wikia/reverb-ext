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
	 * Get a group of mocks
	 *
	 * @return array
	 */
	protected function getMocks() {
		return [
			'mockCentralIdLookup' => $this->getOverloadMock('CentralIdLookup'),
			'mockContent' => $this->getMock('Content'),
			'mockLinksUpdate' => $this->getMock('LinksUpdate'),
			'mockMWNamespace' => $this->getOverloadMock('MWNamespace'),
			'mockOutputPage' => $this->getMock('OutputPage'),
			'mockRevision' => $this->getOverloadMock('Revision'),
			'mockSkinTemplate' => $this->getMock('SkinTemplate'),
			'mockStatus' => $this->getMock('Status'),
			'mockTitle' => $this->getMock('Title'),
			'mockUser' => $this->getOverloadMock('User'),
			'mockWikiPage' => $this->getMock('WikiPage')
		];
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
		extract($this->getMocks());
		$flag = 1;

		$mockEnvironment = $this->getOverloadMock('DynamicSettings\Environment');
		$mockEnvironment->shouldReceive('getSiteKey')->andReturn('master');

		$this->mockGlobalConfig->shouldReceive('get')->with('ReverbNamespace')->andReturn('hydra');
		$this->mockGlobalConfig->shouldReceive('get')->with('ReverbApiEndPoint')->andReturn('http://127.0.0.1:8101/v1');

		$mockCentralIdLookup->shouldReceive('factory')->andReturn($mockCentralIdLookup);
		$mockCentralIdLookup->shouldReceive('centralIdFromLocalUser')->andReturn(1);

		$mockStatus->shouldReceive('isGood')->andReturn(true);
		$mockWikiPage->shouldReceive('getTitle')->andReturn($mockTitle);

		$mockTitle->shouldReceive('getNamespace')->andReturn(NS_USER_TALK);
		$mockTitle->shouldReceive('getText')->andReturn('UserName');

		$mockUser->shouldReceive('newFromName')->andReturn($mockUser);
		$mockUser->shouldReceive('getId')->andReturn(1);
		$mockUser->shouldReceive('isLoggedIn')->andReturn(true);

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
		extract($this->getMocks());
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

	/**
	 * Test onLocalUserCreated
	 *
	 * @covers Hooks:onLocalUserCreated
	 *
	 * @return void
	 */
	public function testOnLocalUserCreated() {
		$hooks = $this->createHooks();
		extract($this->getMocks());
		$result = $hooks->onLocalUserCreated($mockUser, true);

		$this->assertTrue($result);
	}

	/**
	 * Test onUserGroupsChange
	 *
	 * @covers Hooks:onUserGroupsChanged
	 *
	 * @return void
	 */
	public function testOnUserGroupsChanged() {
		$hooks = $this->createHooks();
		extract($this->getMocks());
		$addArray = [];
		$removeArray = [];

		$mockUser->shouldReceive('equals')->with($mockUser)->andReturn(false);

		$result = $hooks->onUserGroupsChanged(
			$mockUser,
			$addArray,
			$removeArray,
			$mockUser
		);

		$this->assertTrue($result);
	}

	/**
	 * Test onLinksUpdateAfterInsert
	 *
	 * @covers Hooks:onLinksUpdateAfterInsert
	 *
	 * @return void
	 */
	public function testOnLinksUpdateAfterInsert() {
		global $wgRequest;
		$wgRequest = $this->getMock('RequestContext');
		$hooks = $this->createHooks();
		extract($this->getMocks());

		$wgRequest->shouldReceive('getVal')->with('wpUndidRevision')->andReturn(false);
		$wgRequest->shouldReceive('getVal')->with('action')->andReturn('notRollback');

		$namespace = 'Reverb';
		$mockTitle->shouldReceive('getNamespace')->andReturn($namespace);
		$mockTitle->shouldReceive('isRedirect')->andReturn(false);
		$mockLinksUpdate->shouldReceive('getTitle')->twice()->set('mRecursive', true)->andReturn($mockTitle);
		$mockMWNamespace->shouldReceive('isContent')->with($namespace)->andReturn(true);

		$mockLinksUpdate->shouldReceive('getTriggeringUser')->andReturn($mockUser);
		$mockRevision->shouldReceive('getId')->andReturn(1);
		$mockLinksUpdate->shouldReceive('getRevision')->twice()->andReturn($mockRevision);
		$result = $hooks->onLinksUpdateAfterInsert(
			$mockLinksUpdate,
			'pagelinks',
			[]
		);

		$this->assertTrue($result);
	}

	/**
	 * Test onArticleRollbackComplete
	 *
	 * @covers Hooks:onArticleRollbackComplete
	 *
	 * @return void
	 */
	public function testOnArticleRollbackComplete() {
		$hooks = $this->createHooks();
		extract($this->getMocks());

		$mockRevision->shouldReceive('getUser')->andReturn($mockUser);
		$mockWikiPage->shouldReceive('getRevision')->andReturn($mockRevision);

		$mockContent->shouldReceive('equals')->with($mockContent)->andReturn(false);
		$mockRevision->shouldReceive('getContent')->twice()->andReturn($mockContent);

		$result = $hooks->onArticleRollbackComplete(
			$mockWikiPage,
			$mockUser,
			$mockRevision,
			$mockRevision
		);

		$this->assertTrue($result);
	}

	/**
	 * Test onBeforePageDisplay
	 *
	 * @covers Hooks:onBeforePageDisplay
	 *
	 * @return void
	 */
	public function testOnBeforePageDisplay() {
		$hooks = $this->createHooks();
		extract($this->getMocks());

		$mockOutputPage->shouldReceive('addModuleStyles')->with('ext.reverb.notifications.styles');
		$mockOutputPage->shouldReceive('addModules')->with('ext.reverb.notifications.scripts');

		$result = $hooks->onBeforePageDisplay(
			$mockOutputPage,
			$mockSkinTemplate
		);

		$this->assertTrue($result);
	}
}
