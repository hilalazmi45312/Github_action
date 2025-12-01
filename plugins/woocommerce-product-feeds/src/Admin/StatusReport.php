<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use Exception;

class StatusReport {

	// Dependencies.
	private Configuration $configuration;
	private TemplateLoader $template_loader;
	private FeedConfigRepository $config_repository;

	/**
	 * The plugin settings, as retrieved from the database.
	 *
	 * @var array
	 */
	private array $settings = [];

	/**
	 * Constructor.
	 *
	 * Store dependencies.
	 *
	 * @param TemplateLoader $template_loader An instance of the template loader.
	 * @param Configuration $common
	 * @param FeedConfigRepository $config_repository
	 */
	public function __construct(
		TemplateLoader $template_loader,
		Configuration $common,
		FeedConfigRepository $config_repository
	) {
		$this->template_loader   = $template_loader;
		$this->configuration     = $common;
		$this->config_repository = $config_repository;
	}

	/**
	 * Render the system status output for this plugin.
	 */
	public function render(): void {
		$this->settings = $this->configuration->get_settings();
		$this->render_options();
		$this->render_field_config();
		$this->render_db_status();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	private function render_field_config() {
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'header',
			[
				'title'      => esc_html( __( 'WooCommerce Google Product Feed fields', 'woocommerce_gpf' ) ),
				'attr_title' => esc_attr( __( 'WooCommerce Google Product Feed fields', 'woocommerce_gpf' ) ),
			]
		);
		foreach ( $this->settings['product_fields'] as $key => $value ) {
			if ( 'on' !== $value ) {
				continue;
			}
			$field_name = ! empty( $this->configuration->product_fields[ $key ]['desc'] ) ? $this->configuration->product_fields[ $key ]['desc'] : $key;
			$status     = '';
			if ( ! empty( $this->settings['product_defaults'][ $key ] ) ) {
				$default_value = $this->settings['product_defaults'][ $key ];
				if ( is_array( $default_value ) ) {
					$default_value = wp_json_encode( $default_value );
				}
				$default_value = esc_html( $default_value );
				$status       .= sprintf(
				// Translators: Placeholder is the "default value" for this field
					__( 'Defaults to &quot;%s&quot;. ', 'woocommerce_gpf' ),
					$default_value
				);
			}
			if ( ! empty( $this->settings['product_prepopulate'][ $key ] ) ) {
				if ( stripos( $this->settings['product_prepopulate'][ $key ], 'description:' ) === 0 ) {
					$description_options = $this->configuration->get_description_prepopulate_options();
					$prepop_value        = $this->settings['product_prepopulate'][ $key ];
					$status             .= $description_options[ $prepop_value ] ?? $prepop_value;
				} else {
					$prepopulate = $this->generate_prepopulate_for_field( $key );
					// Translators: Placeholder is a description of the pre-population rule for this field.
					$status .= sprintf( __( 'Pre-populates from %s.', 'woocommerce_gpf' ), $prepopulate );
				}
			}
			$this->template_loader->output_template_with_variables(
				'woo-gpf-status-report',
				'item',
				[
					'attr_name' => esc_attr( $field_name ),
					'name'      => esc_html( $field_name ),
					'status'    => $status,
				]
			);
		}
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'footer'
		);
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	private function render_options() {
		// Grab the output for the various settings.
		if ( isset( $this->settings['include_variations'] ) && 'on' === $this->settings['include_variations'] ) {
			$include_variations = __( 'Enabled', 'woocommerce_gpf' );
		} else {
			$include_variations = __( '-', 'woocommerce_gpf' );
		}
		if ( isset( $this->settings['send_item_group_id'] ) && 'on' === $this->settings['send_item_group_id'] ) {
			$send_item_group_id = __( 'Enabled', 'woocommerce_gpf' );
		} else {
			$send_item_group_id = __( '-', 'woocommerce_gpf' );
		}
		if ( isset( $this->settings['expanded_schema'] ) && 'on' === $this->settings['expanded_schema'] ) {
			$expanded_schema = __( 'Enabled', 'woocommerce_gpf' );
		} else {
			$expanded_schema = __( '-', 'woocommerce_gpf' );
		}
		$debug_key = get_option( 'woocommerce_gpf_debug_key', __( 'Not set', 'woocommerce_gpf' ) );

		/**
		 * Configured feeds.
		 */
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'header',
			[
				'attr_title' => esc_attr( __( 'WooCommerce Google Product Feed feeds', 'woocommerce_gpf' ) ),
				'title'      => esc_html( __( 'WooCommerce Google Product Feed feeds', 'woocommerce_gpf' ) ),
			]
		);
		$this->render_enabled_feeds();
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'footer'
		);

		/**
		 * Extension options.
		 */
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'header',
			[
				'attr_title' => esc_attr( __( 'WooCommerce Google Product Feed options', 'woocommerce_gpf' ) ),
				'title'      => esc_html( __( 'WooCommerce Google Product Feed options', 'woocommerce_gpf' ) ),
			]
		);
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'item',
			[
				'name'      => esc_html( __( 'Include variations in feed', 'woocommerce_gpf' ) ),
				'attr_name' => esc_attr( __( 'Include variations in feed', 'woocommerce_gpf' ) ),
				'status'    => esc_html( $include_variations ),
			]
		);
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'item',
			[
				'name'      => esc_html( __( 'Send &quot;item group ID&quot;', 'woocommerce_gpf' ) ),
				'attr_name' => esc_attr( __( 'Send &quot;item group ID&quot;', 'woocommerce_gpf' ) ),
				'status'    => esc_html( $send_item_group_id ),
			]
		);
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'item',
			[
				'name'      => esc_html( __( 'Expanded schema markup', 'woocommerce_gpf' ) ),
				'attr_name' => esc_attr( __( 'Expanded schema markup', 'woocommerce_gpf' ) ),
				'status'    => esc_html( $expanded_schema ),
			]
		);

		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'item',
			[
				'name'      => esc_html( __( 'Debug key', 'woocommerce_gpf' ) ),
				'attr_name' => esc_attr( __( 'Debug key', 'woocommerce_gpf' ) ),
				'status'    => esc_html( $debug_key ),
			]
		);
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'footer'
		);
	}


	/**
	 * @return void
	 * @throws Exception
	 */
	private function render_db_status() {

		global $wpdb;

		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'header',
			[
				'attr_title' => esc_attr( __( 'WooCommerce Google Product Feed DB status', 'woocommerce_gpf' ) ),
				'title'      => esc_html( __( 'WooCommerce Google Product Feed DB status', 'woocommerce_gpf' ) ),
			]
		);

		/**
		 * Database versions.
		 */
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'item',
			[
				'name'      => __( 'Database version', 'woocommerce_gpf' ),
				'attr_name' => esc_attr( __( 'Database version', 'woocommerce_gpf' ) ),
				'status'    => WOOCOMMERCE_GPF_DB_VERSION,
			]
		);
		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'item',
			[
				'name'      => __( 'Active database version', 'woocommerce_gpf' ),
				'attr_name' => esc_attr( __( 'Active database version', 'woocommerce_gpf' ) ),
				'status'    => get_option( 'woocommerce_gpf_db_version', __( 'Unknown', 'woocommerce_gpf' ) ),
			]
		);

		/**
		 * wc_gpf_render_cache table status.
		 */
		$table_name = $wpdb->prefix . 'wc_gpf_render_cache';
		$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
		if ( $exists ) {
			$render_cache_statuses = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT name, COUNT(post_id) AS cnt FROM %i GROUP BY name',
					$table_name
				),
				ARRAY_A
			);
			foreach ( $render_cache_statuses as $render_cache_status ) {
				$item_name = 'wc_gpf_render_cache_status (' . $render_cache_status['name'] . ')';
				$status    = sprintf(
				// Translators: %d is the number of items.
					_n( '%d item', '%d items', $render_cache_status['cnt'], 'woocommerce_gpf' ),
					$render_cache_status['cnt']
				);
				$this->template_loader->output_template_with_variables(
					'woo-gpf-status-report',
					'item',
					[
						'name'      => $item_name,
						'attr_name' => esc_attr( $item_name ),
						'status'    => $status,
					]
				);
			}
			if ( empty( $render_cache_statuses ) ) {
				$this->template_loader->output_template_with_variables(
					'woo-gpf-status-report',
					'item',
					[
						'name'      => 'wc_gpf_render_cache',
						'attr_name' => esc_attr( 'wc_gpf_render_cache' ),
						'status'    => __( 'Empty', 'woocommerce_gpf' ),
					]
				);
			}
		} else {
			$this->template_loader->output_template_with_variables(
				'woo-gpf-status-report',
				'item',
				[
					'name'      => 'wc_gpf_render_cache_status',
					'attr_name' => esc_attr( 'wc_gpf_render_cache_status' ),
					'status'    => '<mark class="error">' . __( 'MISSING', 'woocommerce_gpf' ) . '</mark>',
				]
			);
		}

		/**
		 * woocommerce_gpf_google_taxonomy table status.
		 */
		$table_name = $wpdb->prefix . 'woocommerce_gpf_google_taxonomy';
		$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
		if ( $exists ) {
			$taxonomy_cache_statuses = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT locale, COUNT(*) AS cnt FROM %i GROUP BY locale',
					$table_name
				),
				ARRAY_A
			);
			foreach ( $taxonomy_cache_statuses as $taxonomy_cache_status ) {
				$item_name = 'woocommerce_gpf_google_taxonomy (' . $taxonomy_cache_status['locale'] . ')';
				$status    = sprintf(
				// Translators: %d is the number of items
					_n( '%d item', '%d items', $taxonomy_cache_status['cnt'], 'woocommerce_gpf' ),
					$taxonomy_cache_status['cnt']
				);
				$this->template_loader->output_template_with_variables(
					'woo-gpf-status-report',
					'item',
					[
						'name'      => $item_name,
						'attr_name' => esc_attr( $item_name ),
						'status'    => $status,
					]
				);
			}
			if ( empty( $taxonomy_cache_statuses ) ) {
				$this->template_loader->output_template_with_variables(
					'woo-gpf-status-report',
					'item',
					[
						'name'      => 'woocommerce_gpf_google_taxonomy',
						'attr_name' => esc_attr( 'woocommerce_gpf_google_taxonomy' ),
						'status'    => __( 'Empty', 'woocommerce_gpf' ),
					]
				);
			}
		} else {
			$this->template_loader->output_template_with_variables(
				'woo-gpf-status-report',
				'item',
				[
					'name'   => 'woocommerce_gpf_google_taxonomy',
					'status' => '<mark class="error">' . __( 'MISSING', 'woocommerce_gpf' ) . '</mark>',
				]
			);
		}

		$this->template_loader->output_template_with_variables(
			'woo-gpf-status-report',
			'footer',
			[]
		);
	}

	/**
	 * Render the list showing which feed types are enabled.
	 */
	private function render_enabled_feeds(): void {
		$feeds = $this->config_repository->all();
		foreach ( $feeds as $feed_config ) {
			$name = $feed_config->id;
			$this->template_loader->output_template_with_variables(
				'woo-gpf-status-report',
				'item',
				[
					'name'      => esc_html( $name ),
					'attr_name' => esc_attr( $name ),
					'status'    => $feed_config->get_readable_summary(),
				]
			);
		}
	}

	/**
	 * @param $key
	 *
	 * @return string
	 * @throws Exception
	 */
	private function generate_prepopulate_for_field( $key ) {
		if ( stripos( $this->settings['product_prepopulate'][ $key ], 'tax:' ) === 0 ) {
			$prepopulate = sprintf(
			// Translators: Placeholder is the name of the taxonomy
				__( '%s taxonomy', 'woocommerce_gpf' ),
				str_replace( 'tax:', '', esc_html( $this->settings['product_prepopulate'][ $key ] ) )
			);
		} elseif ( stripos( $this->settings['product_prepopulate'][ $key ], 'taxhierarchy:' ) === 0 ) {
			$prepopulate = sprintf(
			// Translators: Placeholder is the name of the taxonomy
				__( '%s taxonomy (full hierarchy)', 'woocommerce_gpf' ),
				str_replace( 'taxhierarchy:', '', esc_html( $this->settings['product_prepopulate'][ $key ] ) )
			);
		} elseif ( stripos( $this->settings['product_prepopulate'][ $key ], 'field:' ) === 0 ) {
			$prepopulate = sprintf(
			// Translators: Placeholder is the name of the product field
				__( 'product %s', 'woocommerce_gpf' ),
				str_replace( 'field:', '', esc_html( $this->settings['product_prepopulate'][ $key ] ) )
			);
		} elseif ( stripos( $this->settings['product_prepopulate'][ $key ], 'meta:' ) === 0 ) {
			$prepopulate = sprintf(
			// Translators: Placeholder is the key of the meta field
				__( '%s meta field', 'woocommerce_gpf' ),
				str_replace( 'meta:', '', esc_html( $this->settings['product_prepopulate'][ $key ] ) )
			);
		} else {
			$description = apply_filters(
				'woocommerce_gpf_prepopulation_description',
				$this->settings['product_prepopulate'][ $key ],
				$this->settings['product_prepopulate'][ $key ]
			);
			$prepopulate = esc_html( $description );
		}

		return $prepopulate;
	}
}
