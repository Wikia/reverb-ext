<?php
/**
 * Test Base
 *
 * @package Tests
 * @author  Samuel Hilson
 * @license GPL-2.0-or-later
 */

namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	/**
	 * Setup the test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Create Mock Class
	 *
	 * @param string $class
	 *
	 * @return Mockery
	 */
	public function getMock($class) {
		return Mockery::mock($class);
	}

	/**
	 * Create Overloaded Mock Class
	 *
	 * @param string $class
	 *
	 * @return Mockery
	 */
	public function getOverloadMock($class) {
		return Mockery::mock('overload:' . $class);
	}

	/**
	 * Tear down the test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		if ($container = Mockery::getContainer()) {
			$this->addToAssertionCount($container->mockery_getExpectationCount());
		}
		Mockery::close();
	}
}
