<?php
/**
 * Post type tests
 *
 * @package Automattic\LegacyRedirector
 */

namespace Automattic\LegacyRedirector\Tests\Integration;

use Automattic\LegacyRedirector\Post_Type;

/**
 * Post type tests class.
 */
final class PostTypeTest extends TestCase {
	/**
	 * Test that the post type exists.
	 *
	 * @coversNothing
	 */
	public function test_post_type_is_registered() {
		$this->assertTrue( post_type_exists( Post_Type::POST_TYPE ) );
	}
}
