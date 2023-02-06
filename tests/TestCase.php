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
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	/**
	 * Container for Mock MediaWikiServices
	 *
	 * @var MediaWikiServices
	 */
	public $mockMWService;

	/**
	 * Container for mock of GlobalConfig
	 *
	 * @var GlobalVarConfig
	 */
	public $mockGlobalConfig;

	/**
	 * Container for mock of ReverbApiClient
	 *
	 * @var Reverb\Client\V1\ClientFactory
	 */
	public $mockReverbApiClient;

	/**
	 * Setup the test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->mockMWService = $this->getOverloadMock( 'MediaWiki\MediaWikiServices' );
		$this->mockGlobalConfig = $this->getOverloadMock( 'GlobalVarConfig' );
		$this->mockReverbApiClient = $this->getOverloadMock( 'Reverb\Client\V1\ClientFactory' );
		$this->mockMWService
			->shouldReceive( 'getService' )
			->with( 'ReverbApiClient' )
			->andReturn( $this->mockReverbApiClient );
		$this->mockMWService->shouldReceive( 'getMainConfig' )->andReturn( $this->mockGlobalConfig );
		$this->mockMWService->shouldReceive( 'getInstance' )->andReturn( $this->mockMWService );
	}

	/**
	 * Create Mock Class
	 *
	 * @param string $class
	 *
	 * @return Mockery
	 */
	public function getMock( $class ) {
		return Mockery::mock( $class );
	}

	/**
	 * Create Overloaded Mock Class
	 *
	 * @param string $class
	 *
	 * @return Mockery
	 */
	public function getOverloadMock( $class ) {
		return Mockery::mock( 'overload:' . $class );
	}

	/**
	 * Create Overloaded Mock for global functions
	 *
	 * @param string $namespace
	 * @param string $function
	 *
	 * @return PHPMockery
	 */
	public function getPHPMock( $namespace, $function ) {
		return PHPMockery::mock( $namespace, $function );
	}

	/**
	 * Tear down the test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		if ( $container = Mockery::getContainer() ) {
			$this->addToAssertionCount( $container->mockery_getExpectationCount() );
		}
		Mockery::close();
	}
}
