<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Local_Forms' ) ) :

	class CRPRO_Local_Forms {

		const FORM_TEMPLATE = 'form.php';
		const FORMS_TABLE = 'cr_local_forms';

		public function __construct() {
			add_filter( 'cr_local_form_output', array( $this, 'output' ), 10, 2 );
			add_filter( 'cr_local_form_insert', array( $this, 'form_insert' ), 10, 2 );
			add_action( 'wp_ajax_crpro_submit_form', array( $this, 'submit_form' ) );
			add_action( 'wp_ajax_nopriv_crpro_submit_form', array( $this, 'submit_form' ) );
			add_action( 'wp_ajax_crpro_upload_media', array( $this, 'upload_media' ) );
			add_action( 'wp_ajax_nopriv_crpro_upload_media', array( $this, 'upload_media' ) );
			add_action( 'wp_ajax_crpro_delete_media', array( $this, 'delete_media' ) );
			add_action( 'wp_ajax_nopriv_crpro_delete_media', array( $this, 'delete_media' ) );
		}

		public function output( $output, $attributes ) {
			$output = '';
			if( $attributes['cr_form_extra'] ) {
				$template = wc_locate_template(
					self::FORM_TEMPLATE,
					'customer-reviews-woocommerce-pro',
					__DIR__ . '/../templates/'
				);
				$cr_form_header = $attributes['cr_form_header'];
				$cr_form_css = plugins_url( '/dist/css/form-app.css', dirname( __FILE__ ) ) . '?ver=' . CRPRO::CRPRO_VERSION;
				$cr_form_js_vendors = plugins_url( '/dist/js/form-vendors.js', dirname( __FILE__ ) ) . '?ver=' . CRPRO::CRPRO_VERSION;
				$cr_form_js_bundle = plugins_url( '/dist/js/form-bundle.js', dirname( __FILE__ ) ) . '?ver=' . CRPRO::CRPRO_VERSION;
				$cr_form_content = $attributes['cr_form_extra'];
				$cr_form_ajax = admin_url( 'admin-ajax.php' );
				ob_start();
				include( $template );
				$output = ob_get_clean();
			}
			return $output;
		}

		public function form_insert( $insert_args, $params ) {
			global $wpdb;
			$extra_col = 'extra';
			$table_name = $params[ 'table_name' ];

			$template = '';
			if( $params[ $extra_col ] ) {
				// preview - advanced form JSON is passed as a parameter
				$template = $params[ $extra_col ];
			} else {
				// regular flow - advanced form JSON is fetched from DB
				$form_template_option = get_option( 'ivole_form_template', array() );
				if(
					isset( $form_template_option[ 'enabled' ] ) &&
					$form_template_option[ 'enabled' ] &&
					isset( $form_template_option[ 'template' ] ) &&
					$form_template_option[ 'template' ] &&
					isset( $form_template_option[ 'template' ][ $insert_args['language'] ] ) &&
					$form_template_option[ 'template' ][ $insert_args['language'] ]
					)
				{
					$template = $form_template_option[ 'template' ][ $insert_args['language'] ];
					$template = json_decode( $template );
				}
			}

			if( $template ) {
				if( property_exists( $template, 'sections' ) ) {
					$sections = $template->sections;
					if( is_array( $sections ) ) {
						// check which sections are present in the form template
						$shop_section; $product_section; $name_section;
						foreach( $sections as $section ) {
							if( property_exists( $section, 'type' ) ) {
								if( 'shop' === $section->type ) {
									$shop_section = $section;
								} elseif( 'product' === $section->type ) {
									$product_section = $section;
								} elseif( 'name' === $section->type ) {
									$name_section = $section;
								}
							}
						}
						// remove the dummy sections
						$template->sections = [];
						// add sections with actual data
						// shop section
						if( $shop_section ) {
							$shop_section->title = CRPRO_Form_Editor_Settings::get_shop_name();
							$shop_section->productId = -1;
							$template->sections[] = $shop_section;
						}
						// products section
						$shopItemIndex = -1;
						if( $product_section && is_array( $params[ 'items' ] ) ) {
							// check if there is a limit on how many products can be displayed on the review form
							$maxProductsNum = 0;
							if ( property_exists( $template, 'maxProductsNum' ) && 0 < $template->maxProductsNum ) {
								$maxProductsNum = $template->maxProductsNum;
								shuffle( $params[ 'items' ] );
							}
							//
							$index = 0;
							foreach( $params['items'] as $indx => $item ) {
								if( -1 === $item['id'] ) {
									$shopItemIndex = $indx;
								} else {
									if ( 0 < $maxProductsNum && $index + 1 > $maxProductsNum  ) {
										// skip the product if the limit of products has been reached
										continue;
									}
									$product = clone $product_section;
									$product->title = $item['name'];
									$product->productPrice = self::format_price( $item['price'] );
									$product->productImage = $item['image'];
									$product->productIndex = $index;
									$product->productId = $item['id'];
									$index++;
									$template->sections[] = $product;
								}
							}
						}
						// add a shop item if a shop section exists
						if( $shop_section && -1 === $shopItemIndex ) {
							$store_item = array( 'id' => -1, 'name' => CRPRO_Form_Editor_Settings::get_shop_name() );
							array_unshift( $params['items'], $store_item );
							$insert_args['items'] = json_encode( $params['items'] );
						}
						// delete a shop item if a shop section does not exist
						if( ! $shop_section && -1 !== $shopItemIndex ) {
							unset( $params['items'][$shopItemIndex] );
							$params['items'] = array_values( $params['items'] );
							$insert_args['items'] = json_encode( $params['items'] );
						}
						// display name section
						if( $name_section ) {
							if( property_exists( $name_section, 'questions' ) ) {
								if( is_array( $name_section->questions ) && 0 < count( $name_section->questions ) ) {
									if( property_exists( $name_section->questions[0], 'customer' ) ) {
										$name_section->questions[0]->customer->firstname = $params[ 'customer' ][ 'firstname' ];
										$name_section->questions[0]->customer->lastname = $params[ 'customer' ][ 'lastname' ];
									}
								}
							}
							$template->sections[] = $name_section;
						}
					}
				}
				if( $params[ 'is_test' ] ) {
					$template->preview = true;
				}
				$template->formId = $insert_args['formId'];
				$insert_args['formHeader'] = $template->title;
				$insert_args['formBody'] = $template->description;
				$insert_args[$extra_col] = json_encode( $template );
			}

			return $insert_args;
		}

		public static function format_price( $price ) {
			$args = array(
				'ex_tax_label'       => false,
				'currency'           => '',
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimals'           => wc_get_price_decimals(),
				'price_format'       => get_woocommerce_price_format(),
			);

			$unformatted_price = $price;
			$negative          = $price < 0;
			$price             = floatval( $negative ? $price * -1 : $price );
			$price             = number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

			if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
				$price = wc_trim_zeros( $price );
			}

			$formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], html_entity_decode( get_woocommerce_currency_symbol( $args['currency'] ) ), $price );
			return $formatted_price;
		}

		public function submit_form() {
			$result_code = 400;
			$result = array(
				'error' => 'Invalid request',
				'details' => 'The review(s) could not be saved',
			);
			if( isset( $_POST['form'] ) && $_POST['form'] ) {
				$form = json_decode( stripslashes( $_POST['form'] ) );
				if( null !== $form ) {
					if( property_exists( $form, 'formId' ) ) {
						// processing required only if it is not a test form
						if( 'test' !== $form->formId ) {
							global $wpdb;
							$table_name = $wpdb->prefix . self::FORMS_TABLE;
							$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_name` WHERE `formId` = %s", $form->formId ) );
							if( null !== $record ) {
								$db_items = json_decode( $record->items, true );

								// get shop and product related sections from the review form
								$review_items = array_filter( $form->sections, function( $itm ) {
									if( property_exists( $itm, 'type' ) ) {
										if( 'shop' === $itm->type || 'product' === $itm->type ) {
											return true;
										}
									}
									return false;
								} );

								$custom_shop_questions = array();
								$custom_product_questions = array();

								foreach( $review_items as $review_item ) {
									foreach( $db_items as $key => $item ) {
										if( intval( $review_item->productId ) === intval( $item['id'] ) ) {
											if( is_array( $review_item->questions ) ) {
												$rating_found = false; $comment_found = false;
												$db_items[$key]['media'] = array();
												foreach( $review_item->questions as $itm ) {
													if( 'rating' === $itm->type ) {
														if(
															property_exists( $itm, 'unableToRemove' ) &&
															1 == $itm->unableToRemove &&
															! $rating_found
														) {
															// standard rating question
															$db_items[$key]['rating'] = $itm->value;
															$rating_found = true;
														} else {
															// additional rating question
															if( 'shop' === $review_item->type ) {
																$custom_shop_questions[] = $itm;
															} else {
																$custom_product_questions[$review_item->productId][] = $itm;
															}
														}
													} elseif ( 'comment' === $itm->type ) {
														if(
															property_exists( $itm, 'unableToRemove' ) &&
															1 == $itm->unableToRemove &&
															! $comment_found
														) {
															// standard comment question
															$db_items[$key]['comment'] = $itm->value;
															$comment_found = true;
														} else {
															// additional comment question
															if( 'shop' === $review_item->type ) {
																$custom_shop_questions[] = $itm;
															} else {
																$custom_product_questions[$review_item->productId][] = $itm;
															}
														}
													} elseif ( 'media' === $itm->type ) {
														if( is_array( $itm->value ) && 0 < count( $itm->value ) ) {
															foreach( $itm->value as $media_file ) {
																if( property_exists( $media_file, 'attachment' ) ) {
																	if( property_exists( $media_file->attachment, 'id' ) ) {
																		$db_items[$key]['media'][] = $media_file->attachment->id;
																	}
																}
															}
														}
													} elseif ( 'radio' === $itm->type || 'checkbox' === $itm->type ) {
														// additional single or multiple choice question
														if( 'shop' === $review_item->type ) {
															$custom_shop_questions[] = $itm;
														} else {
															$custom_product_questions[$review_item->productId][] = $itm;
														}
													}
												}
											}
											break;
										}
									}
								}

								// get the display name and a geo location from the review form
								$displayName = '';
								$geoLocation = '';
								$display_section = array_filter( $form->sections, function( $itm ) {
									if( property_exists( $itm, 'type' ) ) {
										if( 'name' === $itm->type ) {
											return true;
										}
									}
									return false;
								} );
								$display_section = array_values( $display_section );
								if( 0 < count( $display_section ) ) {
									// display name
									if(
										property_exists( $display_section[0], 'questions' ) &&
										is_array( $display_section[0]->questions ) &&
										0 < count( $display_section[0]->questions ) &&
										property_exists( $display_section[0]->questions[0], 'value' ) &&
										property_exists( $display_section[0]->questions[0]->value, 'name' ) &&
										property_exists( $display_section[0]->questions[0]->value->name, 'value' )
									) {
										$displayName = $display_section[0]->questions[0]->value->name->value;
									}
									// geo location
									if(
										property_exists( $display_section[0], 'questions' ) &&
										is_array( $display_section[0]->questions ) &&
										0 < count( $display_section[0]->questions ) &&
										property_exists( $display_section[0]->questions[0], 'value' ) &&
										property_exists( $display_section[0]->questions[0]->value, 'location' ) &&
										property_exists( $display_section[0]->questions[0]->value->location, 'translated' ) &&
										property_exists( $display_section[0]->questions[0]->value->location, 'country' )
									) {
										$geoLocation = new stdClass();
										$geoLocation->code = $display_section[0]->questions[0]->value->location->country;
										$geoLocation->desc = $display_section[0]->questions[0]->value->location->translated;
									}
								}

								$req = new stdClass();
								$req->order = new stdClass();
								$req->order->id = $record->orderId;
								$req->order->display_name = $displayName;
								$req->order->items = array();
								foreach( $db_items as $item ) {
									if( -1 === intval( $item['id'] ) ) {
										$req->order->shop_rating = $item['rating'];
										$req->order->shop_comment = $item['comment'];
										if( is_array( $custom_shop_questions ) && 0 < count( $custom_shop_questions ) ) {
											$req->order->shop_questions = $custom_shop_questions;
										}
									} else {
										// check if the rating property is set to filter out items excluded from a review form by the maximum number of products setting
										if ( isset( $item['rating'] ) ) {
											$product = new stdClass();
											$product->id = $item['id'];
											$product->name = $item['name'];
											$product->price = $item['price'];
											$product->rating = $item['rating'];
											$product->comment = $item['comment'];
											$product->media = $item['media'];
											if(
												isset( $custom_product_questions[$item['id']] ) &&
												is_array( $custom_product_questions[$item['id']] ) &&
												0 < count( $custom_product_questions[$item['id']] )
											) {
												$product->item_questions = $custom_product_questions[$item['id']];
											}
											$req->order->items[] = $product;
										}
									}
								}
								if( $geoLocation ) {
									$req->geo_location = $geoLocation;
								}

								$db_items = json_encode( $db_items );
								$update_result = $wpdb->update( $table_name, array(
									'displayName' => $displayName,
									'items' => $db_items,
									'extra' => json_encode( $form ),
								), array( 'formId' => $form->formId ) );
								if( false !== $update_result ) {
									CR_Endpoint::create_review( $req, true );
									$result_code = 200;
									$result = array(
										'error' => '',
										'details' => '',
									);
								};
							} else {
								$result = array(
									'error' => 'Invalid form ID',
									'details' => 'Form ID could not be found in the database',
								);
							}
						} else {
							$result_code = 200;
							$result = array(
								'error' => '',
								'details' => '',
							);
						}
					} else {
						$result = array(
							'error' => 'Missing form ID',
							'details' => 'Form ID could not be found in the request',
						);
					}
				}
			}
			wp_send_json(
				$result,
				$result_code
			);
		}

		public function upload_media() {
			$return = array(
				'code' => '',
				'href' => '',
				'type' => '',
				'attachment' => '',
			);
			if( isset( $_POST['formId'] ) && isset( $_POST['productId'] ) ) {
				if( isset( $_FILES ) && is_array( $_FILES ) && 0 < count( $_FILES ) ) {
					// check the file size
					$max_size = 1024 * 1024 * get_option( 'ivole_attach_image_size', 25 );
					if ( $max_size < $_FILES['file']['size'] ) {
						$return['code'] = 'size_exceed';
						wp_send_json( $return, 400 );
						return;
					}
					// check the file type
					$file_name_parts = explode( '.', $_FILES['file']['name'] );
					$file_ext = $file_name_parts[ count( $file_name_parts ) - 1 ];
					if( ! CR_Reviews::is_valid_file_type( $file_ext ) ) {
						$return['code'] = 'wrong_extension';
						wp_send_json( $return, 400 );
						return;
					}
					// upload the file
					$attachmentId = media_handle_upload( 'file', 0 );
					if( !is_wp_error( $attachmentId ) ) {
						$upload_key = bin2hex( openssl_random_pseudo_bytes( 10 ) );
						if( false !== update_post_meta( $attachmentId, 'cr-upload-temp-key', $upload_key ) ) {
							// save the attachment id in the database
							if( false !== CR_Local_Forms_Ajax::update_db_item( $_POST['formId'], $_POST['productId'], $attachmentId, false ) ) {
								// return to js
								$return['attachment'] = array(
									'id' => $attachmentId,
									'key' => $upload_key
								);
								$return['href'] = wp_get_attachment_url( $attachmentId );
								if( wp_attachment_is( 'image', $attachmentId ) ) {
									$return['type'] = 'image';
								} else {
									$return['type'] = 'video';
									$return['poster'] = plugins_url( '/img/video.svg', dirname( __FILE__ ) );
								}
							} else {
								$return['code'] = 'unknown';
								wp_send_json( $return, 400 );
								return;
							}
						} else {
							$return['code'] = 'unknown';
							wp_send_json( $return, 400 );
							return;
						}
					} else {
						$return['code'] = 'unknown';
						wp_send_json( $return, 400 );
						return;
					}
				}
			}
			wp_send_json( $return );
		}

		public function delete_media() {
			$return = array(
				'code' => 100,
				'message' => ''
			);
			if( isset( $_POST['attachment'] ) && $_POST['attachment'] ) {
				$image_decoded = json_decode( stripslashes( $_POST['attachment'] ), true );
				if( $image_decoded && is_array( $image_decoded ) ) {
					if( isset( $image_decoded["id"] ) && $image_decoded["id"] ) {
						if( isset( $image_decoded["key"] ) && $image_decoded["key"] ) {
							$attachmentId = intval( $image_decoded["id"] );
							if( 'attachment' === get_post_type( $attachmentId ) ) {
								if( $image_decoded["key"] === get_post_meta( $attachmentId, 'cr-upload-temp-key', true ) ) {
									if( wp_delete_attachment( $attachmentId, true ) ) {
										if( false !== CR_Local_Forms_Ajax::update_db_item( $_POST['formId'], $_POST['productId'], $attachmentId, true ) ) {
											wp_send_json( array( 'message' => 'OK' ), 200 );
										} else {
											$return['code'] = 508;
											$return['message'] = 'Error: could not delete a media ID in the database.';
										}
									} else {
										$return['code'] = 507;
										$return['message'] = 'Error: could not delete the image.';
									}
								} else {
									$return['code'] = 506;
									$return['message'] = 'Error: meta key does not match.';
								}
							} else {
								$return['code'] = 505;
								$return['message'] = 'Error: id does not belong to an attachment.';
							}
						} else {
							$return['code'] = 504;
							$return['message'] = 'Error: image key is not set.';
						}
					} else {
						$return['code'] = 503;
						$return['message'] = 'Error: image id is not set.';
					}
				} else {
					$return['code'] = 502;
					$return['message'] = 'Error: JSON decoding problem.';
				}
			} else {
				$return['code'] = 501;
				$return['message'] = 'Error: no image to delete.';
			}
			wp_send_json( $return, 500 );
		}

	}

endif;
