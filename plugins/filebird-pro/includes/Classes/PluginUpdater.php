<?php
namespace FileBird\Classes;

use enshrined\svgSanitize\Helper;
use FileBird\Classes\Helpers as FileBirdHelpers;
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PluginUpdater {

	private $api_url     = '';
	private $api_data    = array();
	private $plugin_file = '';
	private $name        = '';
	private $slug        = '';
	private $version     = '';
	private $wp_override = false;
	private $beta        = false;
	private $failed_request_cache_key;
	private $fb_token = '';

	public function __construct( $_api_url, $_plugin_file, $_api_data = null, $fb_token = '' ) {

		global $edd_plugin_data;

		$this->api_url                  = trailingslashit( $_api_url );
		$this->api_data                 = $_api_data;
		$this->plugin_file              = $_plugin_file;
		$this->name                     = plugin_basename( $_plugin_file );
		$this->slug                     = $_api_data['slug'];
		$this->version                  = $_api_data['version'];
		$this->wp_override              = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;
		$this->beta                     = ! empty( $this->api_data['beta'] ) ? true : false;
		$this->failed_request_cache_key = 'edd_sl_failed_http_' . md5( $this->api_url );
		$this->fb_token = $fb_token;

		$edd_plugin_data[ $this->slug ] = $this->api_data;
		$this->init();

	}


	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		add_action( 'after_plugin_row', array( $this, 'show_update_notification' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'show_changelog' ) );
	}

	public function check_update( $_transient_data ) {
		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new \stdClass();
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
			return $_transient_data;
		}

		$current = $this->get_repo_api_data();
		if ( false !== $current && is_object( $current ) && isset( $current->new_version ) ) {
			if ( version_compare( $this->version, $current->new_version, '<' ) ) {
				$_transient_data->response[ $this->name ] = $current;
			} else {
				// Populating the no_update information is required to support auto-updates in WordPress 5.5.
				$_transient_data->no_update[ $this->name ] = $current;
			}
		}
		$_transient_data->last_checked           = time();
		$_transient_data->checked[ $this->name ] = $this->version;

		return $_transient_data;
	}

	public function get_repo_api_data() {
		$version_info = $this->get_cached_version_info();

		if ( false === $version_info ) {
			$version_info = $this->api_request(
				'plugin_latest_version',
				array(
					'slug' => $this->slug,
					'beta' => $this->beta,
				)
			);
			if ( ! $version_info ) {
				return false;
			}

			// This is required for your plugin to support auto-updates in WordPress 5.5.
			$version_info->plugin = $this->name;
			$version_info->id     = $this->name;
			$version_info->tested = $this->get_tested_version( $version_info );

			$this->set_version_info_cache( $version_info );
		}

		return $version_info;
	}

	private function get_tested_version( $version_info ) {

		// There is no tested version.
		if ( empty( $version_info->tested ) ) {
			return null;
		}

		// Strip off extra version data so the result is x.y or x.y.z.
		list( $current_wp_version ) = explode( '-', get_bloginfo( 'version' ) );

		// The tested version is greater than or equal to the current WP version, no need to do anything.
		if ( version_compare( $version_info->tested, $current_wp_version, '>=' ) ) {
			return $version_info->tested;
		}
		$current_version_parts = explode( '.', $current_wp_version );
		$tested_parts          = explode( '.', $version_info->tested );

		// The current WordPress version is x.y.z, so update the tested version to match it.
		if ( isset( $current_version_parts[2] ) && $current_version_parts[0] === $tested_parts[0] && $current_version_parts[1] === $tested_parts[1] ) {
			$tested_parts[2] = $current_version_parts[2];
		}

		return implode( '.', $tested_parts );
	}

	public function show_update_notification( $file, $plugin ) {
		if ( $this->name !== $file ) {
			return;
		}

		// Return early if in the network admin, or if this is not a multisite install.
		if ( is_network_admin() || ! is_multisite() ) {
			return;
		}

		// Allow single site admins to see that an update is available.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$is_main_site = is_main_site();

		// Do not print any message if update does not exist.
		$update_cache = get_site_transient( 'update_plugins' );

		if ( ! isset( $update_cache->response[ $this->name ] ) ) {
			if ( ! is_object( $update_cache ) ) {
				$update_cache = new \stdClass();
			}
			$update_cache->response[ $this->name ] = $this->get_repo_api_data();
		}

		// Return early if this plugin isn't in the transient->response or if the site is running the current or newer version of the plugin.
		if ( empty( $update_cache->response[ $this->name ] ) || version_compare( $this->version, $update_cache->response[ $this->name ]->new_version, '>=' ) ) {
			return;
		}

		printf(
			'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
			$this->slug,
			$file,
			in_array( $this->name, $this->get_active_plugins(), true ) ? 'active' : 'inactive'
		);

		echo '<td colspan="3" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt"><p>';

		$changelog_link = '';
		if ( ! empty( $update_cache->response[ $this->name ]->sections->changelog ) ) {
			$changelog_link = add_query_arg(
				array(
					'filebird_sl_action' => 'view_plugin_changelog',
					'plugin'        => urlencode( $this->name ),
					'slug'          => urlencode( $this->slug ),
					'TB_iframe'     => 'true',
					'width'         => 77,
					'height'        => 911,
				),
				self_admin_url( 'index.php' )
			);
		}
		$update_link = add_query_arg(
			array(
				'action' => 'upgrade-plugin',
				'plugin' => urlencode( $this->name ),
			),
			self_admin_url( 'update.php' )
		);

		printf(
			/* translators: the plugin name. */
			esc_html__( 'There is a new version of %1$s available.', 'filebird' ),
			esc_html( $plugin['Name'] )
		);

		if ( ! current_user_can( 'update_plugins' ) ) {
			echo ' ';
			esc_html_e( 'Contact your network administrator to install the update.', 'filebird' );
		} elseif ( empty( $update_cache->response[ $this->name ]->package ) && ! empty( $changelog_link ) ) {
			echo ' ';
			printf(
				/* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */
				__( '%1$sView version %2$s details%3$s.', 'filebird' ),
				'<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
				esc_html( $update_cache->response[ $this->name ]->new_version ),
				'</a>'
			);
		} elseif ( ! empty( $changelog_link ) ) {
			echo ' ';
			if( $is_main_site ) {
				printf(
					__( '%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'filebird' ),
					'<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
					esc_html( $update_cache->response[ $this->name ]->new_version ),
					'</a>',
					'<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">',
					'</a>'
				);
			} else {
				printf(
					/* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */
					__( '%1$sView version %2$s details%3$s.', 'filebird' ),
					'<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
					esc_html( $update_cache->response[ $this->name ]->new_version ),
					'</a>'
				);
			}
			
		} else {
			if( $is_main_site ) {
				printf(
					' %1$s%2$s%3$s',
					'<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">',
					esc_html__( 'Update now.', 'filebird' ),
					'</a>'
				);
			}
		}

		do_action( "in_plugin_update_message-{$file}", $plugin, $plugin );

		echo '</p></div></td></tr>';
	}

	private function get_active_plugins() {
		$active_plugins         = (array) get_option( 'active_plugins' );
		$active_network_plugins = (array) get_site_option( 'active_sitewide_plugins' );

		return array_merge( $active_plugins, array_keys( $active_network_plugins ) );
	}

	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		if ( 'plugin_information' !== $_action ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->slug ) ) {
			return $_data;
		}

		$to_send = array(
			'slug'   => $this->slug,
			'is_ssl' => is_ssl(),
			'fields' => array(
				'banners' => array(),
				'reviews' => false,
				'icons'   => array(),
			),
		);

		// Get the transient where we store the api request for this plugin for 24 hours
		$edd_api_request_transient = $this->get_cached_version_info();

		//If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
		if ( empty( $edd_api_request_transient ) ) {

			$api_response = $this->api_request( 'plugin_information', $to_send );

			// Expires in 3 hours
			$this->set_version_info_cache( $api_response );

			if ( false !== $api_response ) {
				$_data = $api_response;
			}
		} else {
			$_data = $edd_api_request_transient;
		}

		// Convert sections into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
			$_data->sections = $this->convert_object_to_array( $_data->sections );
		}

		// Convert banners into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
			$_data->banners = $this->convert_object_to_array( $_data->banners );
		}

		// Convert icons into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
			$_data->icons = $this->convert_object_to_array( $_data->icons );
		}

		// Convert contributors into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
			$_data->contributors = $this->convert_object_to_array( $_data->contributors );
		}

		if ( ! isset( $_data->plugin ) ) {
			$_data->plugin = $this->name;
		}

		return $_data;
	}

	private function convert_object_to_array( $data ) {
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return array();
		}
		$new_data = array();
		foreach ( $data as $key => $value ) {
			$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
		}

		return $new_data;
	}

	public function http_request_args( $args, $url ) {

		if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
			$args['sslverify'] = $this->verify_ssl();
		}
		return $args;

	}

	private function api_request( $_action, $_data ) {
		$data = array_merge( $this->api_data, $_data );

		if ( $data['slug'] !== $this->slug ) {
			return;
		}

		// Don't allow a plugin to ping itself
		if ( trailingslashit( home_url() ) === $this->api_url ) {
			return false;
		}

		if ( $this->request_recently_failed() ) {
			return false;
		}

		return $this->get_version_from_remote();
	}

	/**
	 * Determines if a request has recently failed.
	 *
	 * @since 1.9.1
	 *
	 * @return bool
	 */
	private function request_recently_failed() {
		$failed_request_details = get_option( $this->failed_request_cache_key );

		// Request has never failed.
		if ( empty( $failed_request_details ) || ! is_numeric( $failed_request_details ) ) {
			return false;
		}

		/*
		 * Request previously failed, but the timeout has expired.
		 * This means we're allowed to try again.
		 */
		if ( time() > $failed_request_details ) {
			delete_option( $this->failed_request_cache_key );

			return false;
		}

		return true;
	}

	private function log_failed_request() {
		update_option( $this->failed_request_cache_key, strtotime( '+1 hour' ) );
	}

	public function show_changelog() {

		if ( empty( $_REQUEST['filebird_sl_action'] ) || 'view_plugin_changelog' !== $_REQUEST['filebird_sl_action'] ) {
			return;
		}

		if ( empty( $_REQUEST['plugin'] ) ) {
			return;
		}

		if ( empty( $_REQUEST['slug'] ) || $this->slug !== $_REQUEST['slug'] ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to install plugin updates', 'filebird' ), esc_html__( 'Error', 'filebird' ), array( 'response' => 403 ) );
		}

		iframe_header( __( 'Plugin Installation', 'filebird' ) );
		echo '<div id="plugin-information">';

		$api = $this->get_repo_api_data();
		$tab = 'plugin-information';
		$plugins_allowedtags = array(
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
			'abbr'       => array( 'title' => array() ),
			'acronym'    => array( 'title' => array() ),
			'code'       => array(),
			'pre'        => array(),
			'em'         => array(),
			'strong'     => array(),
			'div'        => array( 'class' => array() ),
			'span'       => array( 'class' => array() ),
			'p'          => array(),
			'br'         => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'img'        => array(
				'src'   => array(),
				'class' => array(),
				'alt'   => array(),
			),
			'blockquote' => array( 'cite' => true ),
		);
	
		$plugins_section_titles = array(
			// 'description'  => _x( 'Description', 'Plugin installer section title' ),
			// 'installation' => _x( 'Installation', 'Plugin installer section title' ),
			// 'faq'          => _x( 'FAQ', 'Plugin installer section title' ),
			// 'screenshots'  => _x( 'Screenshots', 'Plugin installer section title' ),
			'changelog'    => _x( 'Changelog', 'Plugin installer section title' ),
			// 'reviews'      => _x( 'Reviews', 'Plugin installer section title' ),
			// 'other_notes'  => _x( 'Other Notes', 'Plugin installer section title' ),
		);
	
		// Sanitize HTML.
		$sections = $this->convert_object_to_array( $api->sections );
		foreach ( (array) $sections as $section_name => $content ) {
			$sections[ $section_name ] = wp_kses( $content, $plugins_allowedtags );
		}
	
		foreach ( array( 'version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
			if ( isset( $api->$key ) ) {
				$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
			}
		}
	
		$_tab = esc_attr( $tab );
		$section = 'changelog';
		if ( ! empty( $api->banners ) && ( ! empty( $api->banners->low ) || ! empty( $api->banners->high ) ) ) {
			$_with_banner = 'with-banner';
			$low          = empty( $api->banners->low ) ? $api->banners->high : $api->banners->low;
			$high         = empty( $api->banners->high ) ? $api->banners->low : $api->banners->high;
			?>
			<style type="text/css">
				#plugin-information-title.with-banner {
					background-image: url( <?php echo esc_url( $low ); ?> );
				}
				@media only screen and ( -webkit-min-device-pixel-ratio: 1.5 ) {
					#plugin-information-title.with-banner {
						background-image: url( <?php echo esc_url( $high ); ?> );
					}
				}
			</style>
			<?php
		}
		echo '<div id="plugin-information-scrollable">';
		echo "<div id='{$_tab}-title' class='{$_with_banner}'><div class='vignette'></div><h2>{$api->name}</h2></div>";
		echo "<div id='{$_tab}-tabs' class='{$_with_banner}'>\n";

		foreach ( (array) $api->sections as $section_name => $content ) {
			if( $section_name !== 'changelog' ) {
				continue;
			}
			if ( 'reviews' === $section_name && ( empty( $api->ratings ) || 0 === array_sum( (array) $api->ratings ) ) ) {
				continue;
			}

			if ( isset( $plugins_section_titles[ $section_name ] ) ) {
				$title = $plugins_section_titles[ $section_name ];
			} else {
				$title = ucwords( str_replace( '_', ' ', $section_name ) );
			}

			$class       = ( $section_name === $section ) ? ' class="current"' : '';
			$href        = add_query_arg(
				array(
					'tab'     => $tab,
					'section' => $section_name,
				)
			);
			// $href        = esc_url( $href );
			$href = '#';
			$san_section = esc_attr( $section_name );
			echo "\t<a name='$san_section' href='$href' $class>$title</a>\n";
		}

		echo "</div>\n";
		?>
		<div id="<?php echo $_tab; ?>-content" class='<?php echo $_with_banner; ?>'>
			<div class="fyi">
				<ul>
					<?php if ( ! empty( $api->version ) ) { ?>
						<li><strong><?php _e( 'Version:', 'filebird' ); ?></strong> <?php echo $api->version; ?></li>
					<?php } if ( ! empty( $api->author ) ) { ?>
						<li><strong><?php _e( 'Author:', 'filebird' ); ?></strong> <?php echo links_add_target( $api->author, '_blank' ); ?></li>
					<?php } if ( ! empty( $api->last_updated ) ) { ?>
						<li><strong><?php _e( 'Last Updated:', 'filebird' ); ?></strong>
							<?php
							/* translators: %s: Human-readable time difference. */
							printf( __( '%s ago', 'filebird' ), human_time_diff( strtotime( $api->last_updated ) ) );
							?>
						</li>
					<?php } if ( ! empty( $api->requires ) ) { ?>
						<li>
							<strong><?php _e( 'Requires WordPress Version:', 'filebird' ); ?></strong>
							<?php
							/* translators: %s: Version number. */
							printf( __( '%s or higher', 'filebird' ), $api->requires );
							?>
						</li>
					<?php } if ( ! empty( $api->tested ) ) { ?>
						<li><strong><?php _e( 'Compatible up to:', 'filebird' ); ?></strong> <?php echo $api->tested; ?></li>
					<?php } if ( ! empty( $api->requires_php ) ) { ?>
						<li>
							<strong><?php _e( 'Requires PHP Version:', 'filebird' ); ?></strong>
							<?php
							/* translators: %s: Version number. */
							printf( __( '%s or higher', 'filebird' ), $api->requires_php );
							?>
						</li>
					<?php } if ( isset( $api->active_installs ) ) { ?>
						<li><strong><?php _e( 'Active Installations:', 'filebird' ); ?></strong>
						<?php
						if ( $api->active_installs >= 1000000 ) {
							$active_installs_millions = floor( $api->active_installs / 1000000 );
							printf(
								/* translators: %s: Number of millions. */
								_nx( '%s+ Million', '%s+ Million', $active_installs_millions, 'Active plugin installations' ),
								number_format_i18n( $active_installs_millions )
							);
						} elseif ( $api->active_installs < 10 ) {
							_ex( 'Less Than 10', 'Active plugin installations' );
						} else {
							echo number_format_i18n( $api->active_installs ) . '+';
						}
						?>
						</li>
					<?php } if ( ! empty( $api->homepage ) ) { ?>
						<li><a target="_blank" href="<?php echo esc_url( $api->homepage ); ?>"><?php _e( 'Plugin Homepage &#187;', 'filebird' ); ?></a></li>
					<?php } if ( ! empty( $api->donate_link ) && empty( $api->contributors ) ) { ?>
						<li><a target="_blank" href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;', 'filebird' ); ?></a></li>
					<?php } ?>
				</ul>
				<?php if ( ! empty( $api->rating ) ) { ?>
					<h3><?php _e( 'Average Rating' ); ?></h3>
					<?php
					wp_star_rating(
						array(
							'rating' => $api->rating,
							'type'   => 'percent',
							'number' => $api->num_ratings,
						)
					);
					?>
					<p aria-hidden="true" class="fyi-description">
						<?php
						printf(
							/* translators: %s: Number of ratings. */
							_n( '(based on %s rating)', '(based on %s ratings)', $api->num_ratings ),
							number_format_i18n( $api->num_ratings )
						);
						?>
					</p>
					<?php
				}

				if ( ! empty( $api->ratings ) && array_sum( (array) $api->ratings ) > 0 ) {
					?>
					<h3><?php _e( 'Reviews', 'filebird' ); ?></h3>
					<p class="fyi-description"><?php _e( 'Read all reviews on WordPress.org or write your own!', 'filebird' ); ?></p>
					<?php
					foreach ( $api->ratings as $key => $ratecount ) {
						// Avoid div-by-zero.
						$_rating    = $api->num_ratings ? ( $ratecount / $api->num_ratings ) : 0;
						$aria_label = esc_attr(
							sprintf(
								/* translators: 1: Number of stars (used to determine singular/plural), 2: Number of reviews. */
								_n(
									'Reviews with %1$d star: %2$s. Opens in a new tab.',
									'Reviews with %1$d stars: %2$s. Opens in a new tab.',
									$key
								),
								$key,
								number_format_i18n( $ratecount )
							)
						);
						?>
						<div class="counter-container">
								<span class="counter-label">
									<?php
									printf(
										'<a href="%s" target="_blank" aria-label="%s">%s</a>',
										"https://wordpress.org/support/plugin/{$api->slug}/reviews/?filter={$key}",
										$aria_label,
										/* translators: %s: Number of stars. */
										sprintf( _n( '%d star', '%d stars', $key ), $key )
									);
									?>
								</span>
								<span class="counter-back">
									<span class="counter-bar" style="width: <?php echo 92 * $_rating; ?>px;"></span>
								</span>
							<span class="counter-count" aria-hidden="true"><?php echo number_format_i18n( $ratecount ); ?></span>
						</div>
						<?php
					}
				}
				if ( ! empty( $api->contributors ) ) {
					?>
					<h3><?php _e( 'Contributors', 'filebird' ); ?></h3>
					<ul class="contributors">
						<?php
						foreach ( (array) $api->contributors as $contrib_username => $contrib_details ) {
							$contrib_name = $contrib_details->display_name;
							if ( ! $contrib_name ) {
								$contrib_name = $contrib_username;
							}
							$contrib_name = esc_html( $contrib_name );

							$contrib_profile = esc_url( $contrib_details->profile );
							$contrib_avatar  = esc_url( add_query_arg( 's', '36', $contrib_details->avatar ) );

							echo "<li><a href='{$contrib_profile}' target='_blank'><img src='{$contrib_avatar}' width='18' height='18' alt='' />{$contrib_name}</a></li>";
						}
						?>
					</ul>
							<?php if ( ! empty( $api->donate_link ) ) { ?>
						<a target="_blank" href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;', 'filebird' ); ?></a>
					<?php } ?>
						<?php } ?>
			</div>
			<div id="section-holder">
			<?php

			foreach ( (array) $api->sections as $section_name => $content ) {
				$content = links_add_base_url( $content, 'https://wordpress.org/plugins/' . $api->slug . '/' );
				$content = links_add_target( $content, '_blank' );

				$san_section = esc_attr( $section_name );

				$display = ( $section_name === $section ) ? 'block' : 'none';

				echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
				echo $content;
				echo "\t</div>\n";
			}
			echo "</div>\n";
			echo "</div>\n";
			echo "</div>\n"; // #plugin-information-scrollable
			echo "<div id='$tab-footer'>\n";
			
			echo "</div>\n";
		echo '</div><!--/#plugin-information-->';
		iframe_footer();
		exit;
	}
	private function get_version_from_remote() {
		$args = array(
			'timeout'   => 15,
			'sslverify' => $this->verify_ssl(),
			'body' => array(
				'version' => isset( $this->api_data['version'] ) ? $this->api_data['version'] : false
			)
		);
		if( ! empty( $this->fb_token ) ) {
			$args['body']['fb_token'] = $this->fb_token;
		}

		$request = wp_remote_post(
			$this->api_url . 'json/filebird.json',
			$args	
		);

		if ( is_wp_error( $request ) || ( 200 !== wp_remote_retrieve_response_code( $request ) ) ) {
			$this->log_failed_request();

			return false;
		}

		$request = json_decode( wp_remote_retrieve_body( $request ) );

		if ( $request && isset( $request->sections ) ) {
			$request->sections = maybe_unserialize( $request->sections );
		} else {
			$request = false;
		}

		if ( $request && isset( $request->banners ) ) {
			$request->banners = maybe_unserialize( $request->banners );
		}

		if ( $request && isset( $request->icons ) ) {
			$request->icons = maybe_unserialize( $request->icons );
		}

		if ( ! empty( $request->sections ) ) {
			foreach ( $request->sections as $key => $section ) {
				$request->$key = (array) $section;
			}
		}

		return $request;
	}
	public function get_cached_version_info( $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		$cache = get_option( $cache_key );

		// Cache is expired
		if ( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
			return false;
		}

		// We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
		$cache['value'] = json_decode( $cache['value'] );
		if ( ! empty( $cache['value']->icons ) ) {
			$cache['value']->icons = (array) $cache['value']->icons;
		}
		return $cache['value'];

	}

	public function set_version_info_cache( $value = '', $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

		if( ! FileBirdHelpers::isOptionCollationMb4() && is_object( $value ) && isset( $value->sections ) && is_object( $value->sections ) ) {
			foreach ( $value->sections as $key => $section ) {
				$value->$key = array( FileBirdHelpers::removeEmojis( $value->$key[0] ) );
				$value->sections->$key = FileBirdHelpers::removeEmojis( $value->sections->$key );
			}
		}

		$data = array(
			'timeout' => strtotime( '+3 hours', time() ),
			'value'   => wp_json_encode( $value ),
		);

		update_option( $cache_key, $data, 'no' );
	}

	private function verify_ssl() {
		return true;
	}

	private function get_cache_key() {
		$string = $this->slug . $this->api_data['license'] . $this->beta;
		return 'filebird_pro_sl_' . md5( serialize( $string ) );
	}
}
