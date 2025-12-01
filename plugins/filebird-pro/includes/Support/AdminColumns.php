<?php

namespace FileBird\Support;

defined( 'ABSPATH' ) || exit;

use Filebird\Support\AdminColumns\Column;

class AdminColumns {
	public function __construct() {
		add_action(
            'acp/column_types',
            static function ( \AC\ListScreen $list_screen ): void {
				if ( \ACP()->get_version()->is_lte( new \AC\Plugin\Version( '6.3' ) ) ) {
					return;
				}

				require_once __DIR__ . '/AdminColumns/Column.php';
				require_once __DIR__ . '/AdminColumns/Export.php';

				if ( 'wp-media' === $list_screen->get_key() ) {
					$list_screen->register_column_type( new Column() );
				}
			}
        );
	}
}