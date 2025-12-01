<?php
/**
 * CLI tests
 *
 * @package Automattic\LegacyRedirector
 */

namespace Automattic\LegacyRedirector\Tests\Unit;

use WPCOM_Legacy_Redirector_CLI;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * CLI tests class
 */
final class CliTest extends TestCase {

	/**
	 * Test that the CLI class is instantiable.
	 *
	 * @covers WPCOM_Legacy_Redirector_CLI::__construct
	 */
	public function test_cli_class_is_instantiable() {
		$cli = new WPCOM_Legacy_Redirector_CLI();

		self::assertInstanceOf( WPCOM_Legacy_Redirector_CLI::class, $cli );
	}
}
