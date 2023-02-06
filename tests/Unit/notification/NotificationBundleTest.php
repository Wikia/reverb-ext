<?php
/**
 * NotificationBundleTest
 *
 * @package Tests\Unit
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

namespace Reverb\tests\Notification;

use Reverb\Notification\NotificationBundle;
use Tests\TestCase;

class NotificationBundleTest extends TestCase {
	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setup();
	}

	/**
	 * Test that the 'type' filter gets cast to a string on return.
	 *
	 * @covers NotificationBundle::validateFilters
	 *
	 * @return void
	 */
	public function testValidFilterTypeReturnsString() {
		$filters = NotificationBundle::validateFilters( [ 'type' => 23423 ] );
		$this->assertSame( '23423', $filters['type'] );
	}

	/**
	 * Test that the 'read' filter gets cast to an integer on return.
	 *
	 * @covers NotificationBundle::validateFilters
	 *
	 * @return void
	 */
	public function testValidFilterReadReturnsInteger() {
		$filters = NotificationBundle::validateFilters( [ 'read' => '1' ] );
		$this->assertSame( 1, $filters['read'] );
	}

	/**
	 * Test that the 'read' filter gets cast to an integer on return.
	 *
	 * @covers NotificationBundle::validateFilters
	 *
	 * @return void
	 */
	public function testValidFilterReadStringReturnsZero() {
		$filters = NotificationBundle::validateFilters( [ 'read' => 'NAN' ] );
		$this->assertSame( 0, $filters['read'] );
	}

	/**
	 * Test that the 'unread' filter gets cast to an integer on return.
	 *
	 * @covers NotificationBundle::validateFilters
	 *
	 * @return void
	 */
	public function testValidFilterUnreadReturnsInteger() {
		$filters = NotificationBundle::validateFilters( [ 'unread' => '1' ] );
		$this->assertSame( 1, $filters['unread'] );
	}

	/**
	 * Test that the 'unread' filter gets cast to an integer on return.
	 *
	 * @covers NotificationBundle::validateFilters
	 *
	 * @return void
	 */
	public function testValidFilterUnreadStringReturnsZero() {
		$filters = NotificationBundle::validateFilters( [ 'unread' => 'NAN' ] );
		$this->assertSame( 0, $filters['unread'] );
	}

	/**
	 * Make sure invalid filters are removed.
	 *
	 * @covers NotificationBundle::validateFilters
	 *
	 * @return void
	 */
	public function testValidFilterRemovesBadKeys() {
		$filters = NotificationBundle::validateFilters( [ 'this' => '1', 'that' => '1' ] );
		$this->assertArrayNotHasKey( 'this', $filters );
		$this->assertArrayNotHasKey( 'that', $filters );
	}
}
