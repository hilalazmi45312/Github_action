<?php
/**
 * XML writing section of the plugin
 *
 * @link
 *
 * @package  Webtoffee_Product_Feed_Sync_Pro_Xmlwriter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Webtoffee Product Feed Sync Pro XML Writer
 *
 * @package Webtoffee_Product_Feed_Sync_Pro_Xmlwriter
 */
class Webtoffee_Product_Feed_Sync_Pro_Xmlwriter extends XMLWriter {

	/**
	 * File path
	 *
	 * @var string
	 */
	public $file_path = '';
	/**
	 * Data array
	 *
	 * @var array
	 */
	public $data_ar = '';
	/**
	 * To export
	 *
	 * @var string
	 */
	public $to_export = 'item';

	/**
	 * Export data
	 *
	 * @var array
	 */
	public $export_data = array();

	/**
	 * Head data
	 *
	 * @var array
	 */
	public $head_data = array();

	/**
	 * To export channel
	 *
	 * @var string
	 */
	public $to_export_channel = 'google';

	/**
	 * Form data
	 *
	 * @var array
	 */
	public $form_data = array();

	/**
	 * Constructor
	 *
	 * @param string $file_path File path.
	 * @param array  $form_data Form data.
	 */
	public function __construct( $file_path, $form_data ) {
		$this->file_path = $file_path;
			$this->form_data = $form_data;
	}
	/**
	 * Write to file
	 *
	 * @param array  $export_data Export data.
	 * @param int    $offset Offset.
	 * @param bool   $is_last_offset Is last offset.
	 * @param string $to_export To export.
	 */
	public function write_to_file( $export_data, $offset, $is_last_offset, $to_export ) {
		/**
		 * Filter the items node
		 *
		 * @param string $to_export To export.
		 * @since 1.0.0
		 */
		$to_export = apply_filters( 'wt_feed_xml_writer_items_node', $to_export );

		$item_key = 'item';

		if ( 'google_product_reviews' === $to_export ) {
			$item_key = 'review';
		}
		if ( 'skroutz' === $to_export || 'vivino' === $to_export ) {
			$item_key = 'product'; // skroutz.
		}
		if ( 'fruugo' === $to_export ) {
			$item_key = 'Product';
		}
		if ( 'heureka' === $to_export ) {
			$item_key = 'SHOPITEM';
		}
		if ( 'yandex' === $to_export ) {
			$item_key = 'dummyoffer';
		}

		$this->to_export_channel = $to_export;
		$this->to_export = $item_key;
		$this->export_data = $export_data;
		$this->head_data = $export_data['head_data'];
		$file_path = $this->file_path;

		if ( file_exists( $file_path ) && ( 0 === $offset || '0' === $offset ) ) {
			wp_delete_file( $file_path );
		}

		$this->openMemory();
		$this->setIndent( true );
		$xml_version = '1.0';
		$xml_encoding = 'UTF-8';
		// $xml_standalone = 'no';

		/* write array data to xml */
		if ( ! empty( $export_data['body_data'] ) ) {
			$this->array_to_xml( $this, $this->to_export, $export_data['body_data'], null );
		}

		if ( $is_last_offset ) {
			$prev_body_xml_data = '';
			$body_xml_data = $this->outputMemory(); // taking current offset data.
			$this->endDocument();

			/* need this checking because, if only single batch exists */
			if ( file_exists( $file_path ) && ( 0 !== $offset || '0' !== $offset ) ) {
				$fpr = fopen( $file_path, 'r' );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				$fsize = filesize( $file_path ) ? filesize( $file_path ) : 1;
				// reading previous offset data.
				$prev_body_xml_data = fread( $fpr, $fsize ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			}

			/* create xml starting tag */
			$this->startDocument( $xml_version, $xml_encoding /*, $xml_standalone*/ );
			$doc_xml_data = $this->outputMemory(); // taking xml starting data.
			$this->endDocument();

			$site_name = get_bloginfo( 'name' );
			$site_url = get_site_url();
			/**
			 * Filter the feed description
			 *
			 * @param string $feed_description Feed description.
			 * @since 1.0.0
			 */
			$feed_description = apply_filters( 'wt_feed_xml_head_description', 'WebToffee Product Feed Pro - This product feed is generated with the WebToffee Product Feed Pro. For support queries check out https://www.webtoffee.com/contact or e-mail to: support@webtoffee.com' );
			$xml_start_data = '<rss xmlns:g="http://base.google.com/ns/1.0" xmlns:c="http://base.google.com/cns/1.0" version="2.0">
								<channel>
								<title>
								<![CDATA[ ' . $site_name . ' ]]>
								</title>
								<link><![CDATA[ ' . $site_url . '  ]]></link>
								<description><![CDATA[ ' . $feed_description . ' ]]></description>
								';
			$xml_end_data = '</channel></rss>';

			if ( 'review' === $this->to_export ) {
				$fav_icon_url = get_site_icon_url();
				$xml_start_data = '<feed xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:noNamespaceSchemaLocation=
				"http://www.google.com/shopping/reviews/schema/product/2.3/product_reviews.xsd">
				<version>2.3</version>
					<aggregator>
						<name>Reviews Aggregator</name>
					</aggregator>
					<publisher>
						<name><![CDATA[ ' . $site_name . ' ]]></name>
						<favicon><![CDATA[ ' . $fav_icon_url . '  ]]></favicon>
					</publisher>
				<reviews>';
				$xml_end_data = '</reviews></feed>';
			}

			if ( 'product' === $this->to_export ) {
				$fav_icon_url = get_site_icon_url();
				$xml_start_data = '<mywebstore>
				<created_at>' . gmdate( 'Y-m-d H:i' ) . '</created_at>
				<products>';
				$xml_end_data = '</products></mywebstore>';
			}

			if ( 'google_manufacturer' === $this->to_export_channel ) {

				$xml_start_data = '<rss xmlns:g="http://base.google.com/ns/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="2.0" xsi:noNamespaceSchemaLocation="http://manufacturers.google.com/files/Manufacturer-Center-Product-Feed.xsd">
				<channel>
				<title>
				<![CDATA[ ' . $site_name . ' ]]>
				</title>
				<link><![CDATA[ ' . $site_url . '  ]]></link>
				<description><![CDATA[ ' . $feed_description . ' ]]></description>
				';

			}

			if ( 'fruugo' === $this->to_export_channel ) {
				$xml_start_data = '<Products>';
				$xml_end_data = '</Products>';
			}
			if ( 'heureka' === $this->to_export_channel ) {
				$xml_start_data = '<SHOP>';
				$xml_end_data = '</SHOP>';
			}

			if ( 'yandex' === $this->to_export_channel ) {

				if ( isset( $this->form_data['post_type_form_data']['wt_pf_inc_exc_category'] ) ) {
					$categories = array();
					foreach ( $this->form_data['post_type_form_data']['wt_pf_inc_exc_category'] as $key => $cat_slug ) {
						$categories[] = get_term_by( 'slug', $cat_slug, 'product_cat' );
					}
				} else {

					$args = array(
						'taxonomy' => 'product_cat',
						'get' => 'all',
					);
					$categories = get_categories( $args );
				}

				$category_node = '';
				if ( ! empty( $categories ) ) {
					foreach ( $categories as $category ) {
						$category_node .= '<category id="' . $category->term_id . '">' . $category->name . '</category>';
					}
				}

				$currency = get_woocommerce_currency();
				$xml_start_data = '<yml_catalog date="2020-11-22T14:37:38+03:00">
				<shop>
				<name><![CDATA[ ' . $site_name . ' ]]></name>
				<company><![CDATA[ ' . $site_name . ' ]]></company>
				<url><![CDATA[ ' . $site_url . '  ]]></url>
				<currencies>
					<currency id="' . $currency . '" rate="1"/>
				</currencies>
				<categories>
				' . $category_node . '
				</categories>
				<offers>';
				$xml_end_data = '</offers></shop></yml_catalog>';
			}

			if ( 'vivino' === $this->to_export_channel ) {
				$fav_icon_url = get_site_icon_url();
				$xml_start_data = '<vivino-product-list>
				<meta-data><feed-generation-date>' . gmdate( 'Y-m-d H:i' ) . '</feed-generation-date>
				</meta-data>';
				$xml_end_data = '</vivino-product-list>';
			}

			/* creating xml doc data */
			$xml_data = $doc_xml_data . $xml_start_data . $prev_body_xml_data . $body_xml_data . $xml_end_data;

			$xml_data = str_replace( array( '<dummyoffer>', '</dummyoffer>' ), array( '', '' ), $xml_data );
			// writing the full xml data to file.
			$fp = fopen( $file_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			fwrite( $fp, $xml_data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		} else { // append data to file.
			$xml_data = $this->outputMemory(); // taking xml starting data.
			$this->endDocument();
			if ( 0 === $offset || '0' === $offset ) {
				$fp = fopen( $file_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			} else {
				$fp = fopen( $file_path, 'a+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			}
			fwrite( $fp, $xml_data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
	}

	/**
	 * Start attribute
	 *
	 * @param XMLWriter $xml_writer XML writer.
	 * @param string    $key Key.
	 */
	public function start_attr( &$xml_writer, $key ) {
		$xml_writer->startAttribute( $key );
	}
	/**
	 * Start element
	 *
	 * @param XMLWriter $xml_writer XML writer.
	 * @param string    $key Key.
	 */
	public function start_elm( &$xml_writer, $key ) {
		if ( 'item' !== $key && 'review' !== $this->to_export && 'product' !== $this->to_export && 'label' !== $key && 'value' !== $key && 'fruugo' !== $this->to_export_channel && 'heureka' !== $this->to_export_channel && 'yandex' !== $this->to_export_channel && 'vivino' !== $this->to_export_channel ) {
			$key = 'g:' . sanitize_title( $key );
		}

		$xml_writer->startElement( $key );
	}
	/**
	 * Write element
	 *
	 * @param XMLWriter $xml_writer XML writer.
	 * @param string    $key Key.
	 * @param string    $value Value.
	 */
	public function write_elm( &$xml_writer, $key, $value ) {
		// Check if google feed if needed - As of now facebook also uses the g: attr in the XML feed.
		if ( strpos( $key, 'wtimages_' ) !== false ) {
			$key = 'additional_image_link';
		}
		if ( 'review' === $this->to_export || 'dummyoffer' === $this->to_export || 'product' === $this->to_export || 'label' === $key || 'value' === $key ) {
			$gkey = sanitize_title( $key );
		} else {
			$gkey = 'g:' . sanitize_title( $key );
		}
		if ( 'fruugo' === $this->to_export_channel || 'yandex' === $this->to_export_channel || 'heureka' === $this->to_export_channel || 'pinterest_rss' === $this->to_export_channel || 'vivino' === $this->to_export_channel ) {
			$gkey = $key;
		}
		$xml_writer->writeElement( $gkey, $value );
	}

	/**
	 * Array to XML
	 *
	 * @param XMLWriter $xml_writer XML writer.
	 * @param string    $element_key Element key.
	 * @param array     $element_value Element value.
	 * @param string    $xmlnsurl XMLNS URL.
	 */
	public function array_to_xml( $xml_writer, $element_key, $element_value = array(), $xmlnsurl = null ) {
		if ( ! empty( $xmlnsurl ) ) {
			$my_root_tag = $element_key;
			$xml_writer->startElementNS( null, $element_key, $xmlnsurl );
		} else {
			$my_root_tag = '';
		}

		if ( is_array( $element_value ) ) {
			// handle attributes.
			if ( '@attributes' === $element_key ) {
				foreach ( $element_value as $attribute_key => $attribute_value ) {
					$this->start_attr( $xml_writer, $attribute_key );
					$xml_writer->text( $attribute_value );
					$xml_writer->endAttribute();
				}
				return;
			}

			// handle order elements.
			if ( is_numeric( key( $element_value ) ) ) {
				foreach ( $element_value as $child_element_key => $child_element_value ) {
					if ( $element_key !== $my_root_tag ) {
						$this->start_elm( $xml_writer, $element_key );
					}
					foreach ( $child_element_value as $sibling_element_key => $sibling_element_value ) {
						$this->array_to_xml( $xml_writer, $sibling_element_key, $sibling_element_value );
					}
					$xml_writer->endElement();
				}
			} else {
				/**
				 * Filter the export XML tags
				 *
				 * @param string $element_key Element key.
				 * @since 1.0.0
				 */
				$element_key = apply_filters( 'wt_feed_alter_export_xml_tags', $element_key );
				if ( $element_key !== $my_root_tag ) {
					$this->start_elm( $xml_writer, $element_key );
				}
				foreach ( $element_value as $child_element_key => $child_element_value ) {
					$this->array_to_xml( $xml_writer, $child_element_key, $child_element_value );
				}
				$xml_writer->endElement();
			}
		} else {
			// handle single elements.
			if ( '@value' == $element_key ) {
				$xml_writer->text( $element_value );
			} else {
				if ( empty( $element_value ) || null === $element_value ) {
					return;
				}
				// wrap element in CDATA tag if it contain illegal characters.
				if ( ( null !== $element_value && ! empty( $element_value ) && false !== strpos( $element_value, '<' ) || false !== strpos( $element_value, '>' ) ||

					/**
					 * Filter the XML node wrap CDATA
					 *
					 * @param bool   $wrap_cdata Wrap CDATA.
					 * @param string $element_value Element value.
					 * @since 1.0.0
					 */
					apply_filters( 'wt_iew_xml_node_wrap_cdata', false, $element_value ) ) && 'ratings' !== $element_key ) {
						$arr = explode( ':', $element_key );
					if ( isset( $arr[1] ) ) {
						$xml_writer->startElementNS( $arr[0], $arr[1], $arr[0] );
					} else {
						$this->start_elm( $xml_writer, $element_key );
					}
						$xml_writer->writeCdata( $element_value );
						$xml_writer->endElement();

				} else {
					// Write full namespaced element tag using xmlns.
					$arr = explode( ':', $element_key );
					if ( count( $arr ) > 1 ) {
						$xml_writer->writeElementNS( $arr[0], sanitize_title( $arr[1] ), $arr[0], $element_value );
					} else {
						$this->write_elm( $xml_writer, $element_key, $element_value );
					}
				}
			}
			return;
		}
	}
}
