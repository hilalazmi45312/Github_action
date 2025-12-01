<?php

namespace FileBird\Support\AdminColumns;

use FileBird\Model\Folder as FolderModel;
use AC;
use ACP;

class Column extends AC\Column implements ACP\Export\Exportable {
    private $folders = array();
    private $cached  = array();

    public function __construct() {
        $this->folders = FolderModel::allFolders( 'id,name,parent', null, null, null, OBJECT_K );

        $this->set_type( 'ac-filebird_folder' );
        $this->set_label( __( 'FileBird Folder', 'filebird' ) );
    }

    public function get_current_folder_id( $post_id ) {
        if ( isset( $_GET['fbv'] ) && intval( $_GET['fbv'] ) > 0 ) {
            return intval( $_GET['fbv'] );
        }

        return FolderModel::getFolderIdFromPostId( $post_id ) ?? 0;
    }

    public function get_folder_path( $current_folder_id ) {
        if ( isset( $this->cached[ $current_folder_id ] ) ) {
            return $this->cached[ $current_folder_id ];
        }

        $folder_path     = array();
        $path            = '';
        $folder_from_map = $this->folders[ $current_folder_id ] ?? 0;

        while ( ! is_numeric( $folder_from_map ) ) {
            array_unshift( $folder_path, $folder_from_map->id );
            $folder_from_map = $this->folders[ $folder_from_map->parent ] ?? 0;
        }

        foreach ( $folder_path as $value ) {
            if ( $value !== end( $folder_path ) ) {
                $path .= $this->folders[ $value ]->name . ' / ';
            } else {
                $path .= $this->folders[ $value ]->name;
            }
        }

        $this->cached[ $current_folder_id ] = $path;

        return $path;
    }

    public function get_value( $id ): string {
        $current_folder_id   = $this->get_current_folder_id( $id );
        $current_folder_path = $this->get_folder_path( $current_folder_id );

        if ( ! isset( $this->folders[ $current_folder_id ] ) ) {
            return '';
        }

        return sprintf( '<a data-id="%1$d" href="#" title="%2$s">%3$s</a>', $current_folder_id, $current_folder_path, $this->folders[ $current_folder_id ]->name );
    }

    public function export() {
        return new Export();
    }

    protected function register_settings(): void {
    }

    public function is_valid(): bool {
        return true;
    }

    public function scripts(): void {
    }

}