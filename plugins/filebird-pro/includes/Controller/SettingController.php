<?php

namespace FileBird\Controller;

use FileBird\Classes\Helpers;
use FileBird\Model\SettingModel;
use FileBird\Model\Folder as FolderModel;
use FileBird\Model\UserSettingModel;

defined( 'ABSPATH' ) || exit;

class SettingController {
    public function setSettings( \WP_REST_Request $request ) {
        $params = Helpers::sanitize_array( $request->get_params() );

        SettingModel::getInstance()->setSettings( $params );

		return rest_ensure_response( true );
    }

    public function setUserSettings( \WP_REST_Request $request ) {
        $params = Helpers::sanitize_array( $request->get_params() );

        UserSettingModel::getInstance()->setSettings( $params );
        
        if (current_user_can( 'manage_options' )) {
            SettingModel::getInstance()->setSettings( $params );
        }

		return rest_ensure_response( true );
    }
    public function resetCount( \WP_REST_Request $request ) {
        $params = Helpers::sanitize_array( $request->get_params() );

        FolderModel::resetCount();

		return rest_ensure_response( true );
    }
}
