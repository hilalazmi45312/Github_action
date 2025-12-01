<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Metagauss_License_Migrator {

    const VERSION_OPTION = 'metagauss_license_migration_version';
    const MIGRATION_VERSION = '1.0.0';
    const NEW_OPTION_KEY = 'metagauss_license_data';

    /**
     * Discover patterns, e.g.
     *   Eventprime_Event_Import_Export_license_response
     *   Eventprime_Event_Import_Export_license_key
     *   Eventprime_Event_Import_Export_item_id
     *   Eventprime_Event_Import_Export_license_id
     *
     * We key off *_license_key so we get one row per extension namespace.
     */
    public static function find_old_prefixes() {
        global $wpdb;

        $like = $wpdb->esc_like( '_license_key' );
        $sql  = "
            SELECT option_name
            FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ";
        $rows = $wpdb->get_col( $wpdb->prepare( $sql, '%' . $like ) );

        $prefixes = array();
        foreach ( (array) $rows as $opt_name ) {
            // Remove trailing suffix to get the extension prefix
            $prefix = preg_replace( '/_license_key$/', '', $opt_name );
            if ( $prefix && ! in_array( $prefix, $prefixes, true ) ) {
                $prefixes[] = $prefix;
            }
        }
        return $prefixes;
    }

    public static function maybe_run() {
        // Run once per plugin update OR when explicitly triggered.
        $stored = get_option( self::VERSION_OPTION );
        if ( $stored === self::MIGRATION_VERSION ) {
            return; // already migrated for this version
        }
        self::migrate_all();
        update_option( self::VERSION_OPTION, self::MIGRATION_VERSION, false );
    }

    public static function migrate_all() {
        $prefixes = self::find_old_prefixes();
        if ( empty( $prefixes ) ) {
            return; // nothing to do
        }

        $new_data = get_option( self::NEW_OPTION_KEY, array() );
        if ( ! is_array( $new_data ) ) {
            $new_data = array();
        }

        $site_url_for_dl = home_url(); // used in default download URL
        $to_delete = array();

        foreach ( $prefixes as $prefix ) {
            $old = self::collect_old_record( $prefix );

            // Minimal sanity
            if ( empty( $old['license_key'] ) || empty( $old['item_id'] ) ) {
                continue;
            }

            $license_key = (string) $old['license_key'];
            $item_id     = (int) $old['item_id'];

            // Skip if already migrated for this license+item pair
            if ( isset( $new_data[ $license_key ]['plugins'][ $item_id ] ) ) {
                continue;
            }

            $resp = $old['response'];
            $name = '';
            $status = 'inactive';
            $allowed_sites = null;
            $activation_count = null;
            $expires_ts = null;
            $expire_date_str = '';
            $can_activate = true;

            if ( is_object( $resp ) || is_array( $resp ) ) {
                $o = (object) $resp;

                // Name
                if ( ! empty( $o->item_name ) ) {
                    $name = (string) $o->item_name;
                }

                // Status
                if ( isset( $o->license ) ) {
                    $status = ( $o->license === 'valid' ) ? 'active' : 'inactive';
                }

                // Allowed sites and activation counts
                if ( isset( $o->license_limit ) && is_numeric( $o->license_limit ) ) {
                    $allowed_sites = (int) $o->license_limit;
                }
                if ( isset( $o->site_count ) && is_numeric( $o->site_count ) ) {
                    $activation_count = (int) $o->site_count;
                } elseif ( isset( $o->activations_left ) && is_numeric( $o->activations_left ) && isset( $allowed_sites ) ) {
                    $activation_count = max( 0, (int) $allowed_sites - (int) $o->activations_left );
                }

                // Expiration
                if ( ! empty( $o->expires ) && $o->expires !== 'lifetime' ) {
                    $maybe = strtotime( $o->expires );
                    if ( $maybe ) {
                        $expires_ts = $maybe;
                        $expire_date_str = date_i18n( 'F j, Y', $expires_ts );
                    }
                } elseif ( ! empty( $o->expires ) && $o->expires === 'lifetime' ) {
                    $expires_ts = 0;
                    $expire_date_str = 'Lifetime';
                }
            }

            // Slug resolution (default: derive from prefix)
            $slug = self::resolve_slug_from_prefix( $prefix, $name );

            /**
             * Allow developers to override the slug mapping.
             * return string $slug
             */
            $slug = apply_filters( 'metagauss_license_slug_map', $slug, $prefix, $name, $old );

            // Version best-effort (read from plugin header if available)
            $version = self::try_find_version_by_slug( $slug );

            // Sites (best effort: we don't have sites list in old data; leave empty)
            $sites = array();
            /**
             * Allow injecting sites during migration if you keep them somewhere.
             * return string[] $sites
             */
            $sites = apply_filters( 'metagauss_license_sites_map', $sites, $prefix, $old );

            // Download URL (filterable)
            $download_url = add_query_arg( array(
                'license_key' => $license_key,
                'slug'        => $slug,
                'url'         => rawurlencode( wp_parse_url( $site_url_for_dl, PHP_URL_HOST ) . wp_parse_url( $site_url_for_dl, PHP_URL_PATH ) ),
            ), 'https://theeventprime.com/wp-json/custom/v1/plugin-download' );

            /**
             * Allow overriding the download URL pattern if needed.
             */
            $download_url = apply_filters(
                'metagauss_license_download_url',
                $download_url,
                $license_key,
                $slug,
                $item_id,
                $old
            );

            // Build the new node
            if ( ! isset( $new_data[ $license_key ] ) ) {
                $new_data[ $license_key ] = array();
            }
            if ( ! isset( $new_data[ $license_key ]['plugins'] ) ) {
                $new_data[ $license_key ]['plugins'] = array();
            }

            $new_data[ $license_key ]['plugins'][ $item_id ] = array(
                'name'            => $name ?: self::humanize_from_slug( $slug ),
                'slug'            => $slug,
                'version'         => $version,
                'license_key'     => $license_key,
                'download_url'    => $download_url,
                'expiration'      => $expires_ts ?? 0,
                'allowed_sites'   => $allowed_sites ?? 1,
                'sites'           => $sites,
                'activation_count'=> $activation_count ?? 0,
                'download_id'     => $item_id,
                'expire_date'     => $expire_date_str ?: ( $expires_ts ? date_i18n( 'F j, Y', $expires_ts ) : '' ),
                'status'          => $status,
                'can_activate'    => ( $status === 'active' || $can_activate ) ? true : false,
            );

            // Only delete old options after we've safely written the new blob.
            // We'll do the actual deletion after the update_option below.
            $to_delete[] = $prefix . '_license_response';
            $to_delete[] = $prefix . '_license_key';
            $to_delete[] = $prefix . '_item_id';
            $to_delete[] = $prefix . '_license_id';
        }

        // Persist the new consolidated data
        update_option( self::NEW_OPTION_KEY, $new_data, false );

        // Cleanup
        if ( ! empty( $to_delete ) ) {
            foreach ( array_unique( $to_delete ) as $opt ) {
                delete_option( $opt );
            }
        }
    }

    /**
     * Collect all old fields for a given prefix.
     */
    protected static function collect_old_record( $prefix ) {
        $response    = get_option( $prefix . '_license_response', null );
        $license_key = get_option( $prefix . '_license_key', '' );
        $item_id     = get_option( $prefix . '_item_id', '' );
        $license_id  = get_option( $prefix . '_license_id', '' );

        $response = self::maybe_unserialize_safely( $response );

        // Prefer explicit item_id; fallback to license_id if empty.
        if ( empty( $item_id ) && ! empty( $license_id ) ) {
            $item_id = $license_id;
        }

        return array(
            'response'    => $response,
            'license_key' => $license_key,
            'item_id'     => $item_id,
        );
    }

    /**
     * get_option returns already-unserialized most of the time, but guard anyway.
     */
    protected static function maybe_unserialize_safely( $val ) {
        if ( is_string( $val ) ) {
            $trim = trim( $val );
            // Serialized object or array starts with O: or a:
            if ( preg_match( '/^(O|a):\d+:/', $trim ) ) {
                $tmp = @unserialize( $trim );
                if ( $tmp !== false || $trim === 'b:0;' ) {
                    return $tmp;
                }
            }
        }
        return $val;
    }

    protected static function resolve_slug_from_prefix( $prefix, $fallback_name = '' ) {
        // Example: Eventprime_Event_Import_Export -> eventprime-events-import-export
        $base = $fallback_name ?: $prefix;
        $base = preg_replace( '/^Eventprime_/', 'EventPrime ', $base );
        $base = trim( $base );
        $slug = strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $base ) );
        $slug = trim( $slug, '-' );
        // Special cases can be filtered via metagauss_license_slug_map
        return $slug;
    }

    protected static function humanize_from_slug( $slug ) {
        $s = str_replace( '-', ' ', $slug );
        $s = ucwords( $s );
        return $s;
    }

    protected static function try_find_version_by_slug( $slug ) {
        // Try to locate plugin by slug; if not found, return empty string.
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all = get_plugins();
        foreach ( $all as $file => $headers ) {
            // match "slug/slug.php" or filename contains slug
            if ( strpos( $file, $slug . '/' ) === 0 || stripos( $file, $slug ) !== false ) {
                return isset( $headers['Version'] ) ? (string) $headers['Version'] : '';
            }
        }
        return '';
    }
}
