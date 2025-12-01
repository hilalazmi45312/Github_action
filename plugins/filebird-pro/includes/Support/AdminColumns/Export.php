<?php

namespace FileBird\Support\AdminColumns;

use ACP\Export\Service;
use FileBird\Model\Folder as FolderModel;


class Export implements Service {
    public function get_value( $id ) {
        $folder = FolderModel::getFolderFromPostId( $id );

        if ( empty( $folder ) ) {
            return __( 'Uncategorized', 'filebird' );
        }

        return sprintf( '<a data-id="%1$d">%2$s</a>', $folder[0]->folder_id, $folder[0]->name );
    }
}