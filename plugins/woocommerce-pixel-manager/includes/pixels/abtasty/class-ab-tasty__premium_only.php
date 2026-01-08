<?php

namespace SweetCode\Pixel_Manager\Pixels\ABTasty;

use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

class AB_Tasty {

	public static function inject_script() {

		// @formatter:off
		?>

		<script type="text/javascript"
				src="https://try.abtasty.com/<?php echo esc_html(Options::get_ab_tasty_account_id()); ?>.js">
		</script>

		<?php
		// @formatter:on
	}
}
