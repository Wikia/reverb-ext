<?php
/**
 * NotificationTest
 *
 * @package Tests\Unit
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

namespace Tests\Unit\Includes\Notification;

use Hydrawiki\Reverb\Client\V1\Resources\Notification as NotificationResource;
use Reverb\Notification\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase {
	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setup();
	}

	/**
	 * Make sure the ID accessed by Notification on the resource is the same.
	 *
	 * @covers Notification::getId
	 *
	 * @return void
	 */
	public function testIdMatches() {
		$resource = new NotificationResource();
		$resource->setId(48);

		$notification = new Notification($resource);

		$this->assertSame(48, $notification->getId());
	}

	/**
	 * Make sure the type accessed by Notification on the resource is the same.
	 *
	 * @covers Notification::getType
	 *
	 * @return void
	 */
	public function testTypeIsTestType() {
		$resource = new NotificationResource();
		$resource->setAttributes(['type' => 'test-type']);

		$notification = new Notification($resource);

		$this->assertSame('test-type', $notification->getType());
	}

	/**
	 * Make sure the type accessed by Notification on the resource is the same.
	 *
	 * @covers Notification::getType
	 * @covers Notification::setType
	 *
	 * @return void
	 */
	public function testSetTypeAsTestType() {
		$resource = new NotificationResource();

		$notification = new Notification($resource);
		$notification->setType('test-type');

		$this->assertSame('test-type', $notification->getType());
	}
}
