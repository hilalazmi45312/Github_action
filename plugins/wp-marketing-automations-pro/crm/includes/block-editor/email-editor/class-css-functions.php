<?php
/**
 * This file handles the Block Patterns Register.
 *
 * @package BWFBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register Block Patterns.
 *
 */
if ( ! class_exists( 'BWF_EMAILBLOCKS_CSS' ) ) {
	#[AllowDynamicProperties]
	class BWF_EMAILBLOCKS_CSS {
		/**
		 * Instance.
		 *
		 * @access private
		 * @var object Instance
		 * @since 1.2.0
		 */
		private static $instance;

		private static $gFonts = [
			'Arvo',
			'Lato',
			'Lora',
			'Merriweather',
			'Merriweather Sans',
			'Noticia Text',
			'Open Sans',
			'Playfair Display',
			'Roboto',
			'Source Sans Pro'
		];

		/**
		 * Initiator.
		 *
		 * @return object initialized object of class.
		 * @since 1.2.0
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get Border Radius CSS
		 *
		 * @param array $attributes Border array
		 * @param string $key Key to lookup in attributes
		 * @param string $screen Screen value
		 * @param bool $important Flag for !important
		 * @param string $default Default CSS value
		 * @param string $g_key Global key for fallback values
		 *
		 * @return string            CSS for border radius
		 */
		public function getBorderRadius( $attributes, $key, $screen, $important = false, $default = '', $g_key = '' ) {
			$globals     = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [];
			$isImportant = $important ? ' !important' : '';
			$unit        = isset( $attributes[ $key ][ $screen ]['radius_unit'] ) ? $attributes[ $key ][ $screen ]['radius_unit'] : 'px';

			// Function to get value from attributes or return null if not found
			$getValue = function ( $attributeKey ) use ( $attributes, $key, $screen ) {
				return isset( $attributes[ $key ][ $screen ][ $attributeKey ] ) && $attributes[ $key ][ $screen ][ $attributeKey ] !== '' ? $attributes[ $key ][ $screen ][ $attributeKey ] : null;
			};

			// Get the radius values from attributes
			$topLeft     = $getValue( 'top-left' );
			$topRight    = $getValue( 'top-right' );
			$bottomLeft  = $getValue( 'bottom-left' );
			$bottomRight = $getValue( 'bottom-right' );

			// Check if all attributes values are missing and if g_key is available in globals
			if ( ( $topLeft === null && $topRight === null && $bottomLeft === null && $bottomRight === null ) && isset( $globals[ $g_key ][ $screen ] ) ) {
				$topLeft     = isset( $globals[ $g_key ][ $screen ]['top-left'] ) ? $globals[ $g_key ][ $screen ]['top-left'] : null;
				$topRight    = isset( $globals[ $g_key ][ $screen ]['top-right'] ) ? $globals[ $g_key ][ $screen ]['top-right'] : null;
				$bottomLeft  = isset( $globals[ $g_key ][ $screen ]['bottom-left'] ) ? $globals[ $g_key ][ $screen ]['bottom-left'] : null;
				$bottomRight = isset( $globals[ $g_key ][ $screen ]['bottom-right'] ) ? $globals[ $g_key ][ $screen ]['bottom-right'] : null;
				$unit        = isset( $globals[ $g_key ][ $screen ]['radius_unit'] ) ? $globals[ $g_key ][ $screen ]['radius_unit'] : 'px';
			}

			// If all values are still missing, return the default
			if ( $topLeft === null && $topRight === null && $bottomLeft === null && $bottomRight === null ) {
				return $default;
			}

			// Construct a single border-radius property
			$radius = sprintf( 'border-radius: %s%s %s%s %s%s %s%s%s;', $topLeft !== null ? $topLeft : '0', $unit, $topRight !== null ? $topRight : '0', $unit, $bottomRight !== null ? $bottomRight : '0', $unit, $bottomLeft !== null ? $bottomLeft : '0', $unit, $isImportant );

			return $radius;
		}


		public function getBorderWidth( $attributes, $key, $screen, $important = false ) {
			$width = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) ) {
				$border = $attributes[ $key ];
				if ( isset( $border[ $screen ]['top'] ) && $border[ $screen ]['top'] !== '' && $border[ $screen ]['top'] !== '0' ) {
					$width .= 'border-top-width: ' . $border[ $screen ]['top'] . $border[ $screen ]['unit'] . ( $important ? ' !important;' : ';' );
				}

				if ( isset( $border[ $screen ]['bottom'] ) && $border[ $screen ]['bottom'] !== '' && $border[ $screen ]['bottom'] !== '0' ) {
					$width .= 'border-bottom-width: ' . $border[ $screen ]['bottom'] . $border[ $screen ]['unit'] . ( $important ? ' !important;' : ';' );
				}

				if ( isset( $border[ $screen ]['left'] ) && $border[ $screen ]['left'] !== '' && $border[ $screen ]['left'] !== '0' ) {
					$width .= 'border-left-width: ' . $border[ $screen ]['left'] . $border[ $screen ]['unit'] . ( $important ? ' !important;' : ';' );
				}

				if ( isset( $border[ $screen ]['right'] ) && $border[ $screen ]['right'] !== '' && $border[ $screen ]['right'] !== '0' ) {
					$width .= 'border-right-width: ' . $border[ $screen ]['right'] . $border[ $screen ]['unit'] . ( $important ? ' !important;' : ';' );
				}

			}

			return $width;
		}


		public function getBorderStyle( $attributes, $key, $screen, $important = false ) {
			$style = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) ) {
				$border = $attributes[ $key ];
				if ( isset( $border[ $screen ] ) && isset( $border[ $screen ]['top'] ) && ( $border[ $screen ]['top'] === '' || $border[ $screen ]['top'] === '0' ) ) {
					$style .= ! $important ? 'border-top-style:none;' : '';
				} else if ( isset( $border[ $screen ]['style'] ) ) {
					$style .= 'border-top-style: ' . $border[ $screen ]['style'] . ( $important ? ' !important;' : ';' );
				}

				if ( isset( $border[ $screen ] ) && isset( $border[ $screen ]['bottom'] ) && ( $border[ $screen ]['bottom'] === '' || $border[ $screen ]['bottom'] === '0' ) ) {
					$style .= ! $important ? 'border-bottom-style:none;' : '';
				} else if ( isset( $border[ $screen ]['style'] ) ) {
					$style .= 'border-bottom-style: ' . $border[ $screen ]['style'] . ( $important ? ' !important;' : ';' );
				}

				if ( isset( $border[ $screen ] ) && isset( $border[ $screen ]['left'] ) && ( $border[ $screen ]['left'] === '' || $border[ $screen ]['left'] === '0' ) ) {
					$style .= ! $important ? 'border-left-style:none;' : '';
				} else if ( isset( $border[ $screen ]['style'] ) ) {
					$style .= 'border-left-style: ' . $border[ $screen ]['style'] . ( $important ? ' !important;' : ';' );
				}

				if ( isset( $border[ $screen ] ) && isset( $border[ $screen ]['right'] ) && ( $border[ $screen ]['right'] === '' || $border[ $screen ]['right'] === '0' ) ) {
					$style .= ! $important ? 'border-right-style:none;' : '';
				} else if ( isset( $border[ $screen ]['style'] ) ) {
					$style .= 'border-right-style: ' . $border[ $screen ]['style'] . ( $important ? ' !important;' : ';' );
				}

			}

			return $style;
		}

		public function getBorderColor( $attributes, $key, $screen, $important = false ) {
			$color = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) ) {
				$border = $attributes[ $key ];
				$color  .= ! empty( $border[ $screen ]['color_top'] ) ? ( 'border-top-color: ' . $border[ $screen ]['color_top'] . ( $important ? ' !important;' : ';' ) ) : '';
				$color  .= ! empty( $border[ $screen ]['color_bottom'] ) ? ( 'border-bottom-color: ' . $border[ $screen ]['color_bottom'] . ( $important ? ' !important;' : ';' ) ) : '';
				$color  .= ! empty( $border[ $screen ]['color_right'] ) ? ( 'border-right-color: ' . $border[ $screen ]['color_right'] . ( $important ? ' !important;' : ';' ) ) : '';
				$color  .= ! empty( $border[ $screen ]['color_left'] ) ? ( 'border-left-color: ' . $border[ $screen ]['color_left'] . ( $important ? ' !important;' : ';' ) ) : '';

			}

			return $color;
		}


		public function getPadding( $attributes, $key, $screen, $important = false, $default = '', $g_key = '' ) {
			$globals     = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [];
			$isImportant = $important ? ' !important' : '';
			$unit        = isset( $attributes[ $key ][ $screen ]['unit'] ) ? $attributes[ $key ][ $screen ]['unit'] : 'px';

			// Function to get value from attributes or return '0' if not found
			$getValue = function ( $attributeKey ) use ( $attributes, $key, $screen ) {
				return isset( $attributes[ $key ][ $screen ][ $attributeKey ] ) && $attributes[ $key ][ $screen ][ $attributeKey ] !== '' ? $attributes[ $key ][ $screen ][ $attributeKey ] : '0';
			};

			// Get padding values from attributes
			$top    = $getValue( 'top' );
			$right  = $getValue( 'right' );
			$bottom = $getValue( 'bottom' );
			$left   = $getValue( 'left' );

			// Check if all attribute values are missing and if g_key is available in globals
			if ( ( $top === '0' && $right === '0' && $bottom === '0' && $left === '0' ) && isset( $globals[ $g_key ][ $screen ] ) ) {
				$top    = isset( $globals[ $g_key ][ $screen ]['top'] ) ? $globals[ $g_key ][ $screen ]['top'] : '0';
				$right  = isset( $globals[ $g_key ][ $screen ]['right'] ) ? $globals[ $g_key ][ $screen ]['right'] : '0';
				$bottom = isset( $globals[ $g_key ][ $screen ]['bottom'] ) ? $globals[ $g_key ][ $screen ]['bottom'] : '0';
				$left   = isset( $globals[ $g_key ][ $screen ]['left'] ) ? $globals[ $g_key ][ $screen ]['left'] : '0';
				$unit   = isset( $globals[ $g_key ][ $screen ]['unit'] ) ? $globals[ $g_key ][ $screen ]['unit'] : 'px';
			}

			// If all values are still zero, return the default
			if ( $top === '0' && $right === '0' && $bottom === '0' && $left === '0' ) {
				return $default;
			}

			// Construct a single padding property
			$padding = sprintf( 'padding: %s%s %s%s %s%s %s%s%s;', $top, $unit, $right, $unit, $bottom, $unit, $left, $unit, $isImportant );

			return $padding;
		}

		public function getMsoPadding( $attributes, $key, $screen, $important = false, $default = '', $g_key = '' ) {
			$globals     = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [];
			$isImportant = $important ? ' !important' : '';
			$unit        = isset( $attributes[ $key ][ $screen ]['unit'] ) ? $attributes[ $key ][ $screen ]['unit'] : 'px';

			// Function to get value from attributes or return '0' if not found
			$getValue = function ( $attributeKey ) use ( $attributes, $key, $screen ) {
				return isset( $attributes[ $key ][ $screen ][ $attributeKey ] ) && $attributes[ $key ][ $screen ][ $attributeKey ] !== '' ? $attributes[ $key ][ $screen ][ $attributeKey ] : '0';
			};

			// Get padding values from attributes
			$top    = $getValue( 'top' );
			$right  = $getValue( 'right' );
			$bottom = $getValue( 'bottom' );
			$left   = $getValue( 'left' );

			// Check if all attribute values are missing and if g_key is available in globals
			if ( ( $top === '0' && $right === '0' && $bottom === '0' && $left === '0' ) && isset( $globals[ $g_key ][ $screen ] ) ) {
				$top    = isset( $globals[ $g_key ][ $screen ]['top'] ) ? $globals[ $g_key ][ $screen ]['top'] : '0';
				$right  = isset( $globals[ $g_key ][ $screen ]['right'] ) ? $globals[ $g_key ][ $screen ]['right'] : '0';
				$bottom = isset( $globals[ $g_key ][ $screen ]['bottom'] ) ? $globals[ $g_key ][ $screen ]['bottom'] : '0';
				$left   = isset( $globals[ $g_key ][ $screen ]['left'] ) ? $globals[ $g_key ][ $screen ]['left'] : '0';
				$unit   = isset( $globals[ $g_key ][ $screen ]['unit'] ) ? $globals[ $g_key ][ $screen ]['unit'] : 'px';
			}

			// If all values are still zero, return the default
			if ( $top === '0' && $right === '0' && $bottom === '0' && $left === '0' ) {
				return $default;
			}

			// Construct a single mso-padding property
			$msoPadding = sprintf( 'mso-padding-alt: %s%s %s%s %s%s %s%s%s;', $top, $unit, $right, $unit, $bottom, $unit, $left, $unit, $isImportant );

			return $msoPadding;
		}


		public function getFontStyles( $attributes, $key, $screen = 'desktop', $important = false, $default = '' ) {
			$newFont       = '';
			$has_important = $important ? ' !important;' : ';';
			$font_styles   = isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) ? $attributes[ $key ][ $screen ] : [];

			if ( ! empty( $font_styles ) ) {
				if ( isset( $font_styles['size'] ) && $font_styles['size'] !== '' ) {
					$newFont .= "font-size: " . $font_styles['size'] . "px$has_important";
				}

				if ( isset( $font_styles['family'] ) && $font_styles['family'] !== '' ) {
					$newFont .= "font-family:" . $font_styles['family'] . $has_important;
				} else if ( false !== $default && false === strpos( $newFont, 'font-family' ) ) {
					$newFont .= "font-family: arial, Helvetica, sans-serif" . $has_important;
				}

				if ( isset( $font_styles['weight'] ) && $font_styles['weight'] !== '' ) {
					$newFont .= "font-weight:" . $font_styles['weight'] . $has_important;
				}
			}

			// return default if empty
			// if( empty( $newFont ) ) {
			//     $default .= !str_contains($default, 'font-family') && $screen === 'desktop' ? 'font-family: Arial, Helvetica, sans-serif;' : '';
			//     return $default;
			// }
			$newFont .= ( false === strpos( $newFont, 'font-size' ) && false !== strpos( $default, 'font-size' ) && $screen === 'desktop' ) ? $default : '';

			return '' . $newFont;
		}

		private function getFontWithBackupFont( $font_family, $globals ) {
			if ( in_array( $font_family, self::$gFonts ) && isset( $globals ) && isset( $globals['backupFont'] ) && isset( $globals['backupFont']['desktop'] ) && isset( $globals['backupFont']['desktop']['family'] ) && ! empty( $globals['backupFont']['desktop']['family'] ) ) {
				return $font_family . ',' . $globals['backupFont']['desktop']['family'];
			}

			return $font_family;

		}

		/**
		 * Funtion to get font family from attributes / global setting or default
		 */
		public function getFontFamily( $attrs = [], $key = 'font', $screen = 'desktop', $default_val = '', $important = false ) {

			$globals = isset( $attrs['global'] ) ? $attrs['global'] : ( class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [] ); //get global setting from atttributes if wc block else static method

			$has_important = false !== $important ? ' !important;' : ';';

			$font_family = '';

			if ( false === $default_val && ( empty( $attrs[ $key ] ) || empty( $attrs[ $key ][ $screen ] ) ) && ( empty( $globals ) || empty( $globals['font']['desktop']['family'] ) ) ) {
				return $font_family;
			}

			if ( ! empty( $attrs[ $key ] ) && ! empty( $attrs[ $key ][ $screen ] ) && ! empty( $attrs[ $key ][ $screen ]['family'] ) ) {
				$font_family = 'font-family: ' . $this->getFontWithBackupFont( $attrs[ $key ][ $screen ]['family'], $globals ) . $has_important;

				return $font_family;
			}

			if ( ! empty( $globals['font'] ) && ! empty( $globals['font']['desktop'] ) && ! empty( $globals['font']['desktop']['family'] ) ) {
				$font_family = 'font-family: ' . $this->getFontWithBackupFont( $globals['font']['desktop']['family'], $globals ) . $has_important;

				return $font_family;
			}

			if ( ! empty( $default_val ) ) {
				return ( false !== strpos( $default_val, 'font-family' ) ) ? $default_val : 'font-family: ' . $default_val . $has_important;
			} else if ( false === $default_val ) {
				return '';
			}

			return 'font-family: Arial, Helvetica, sans-serif' . $has_important;
		}

		/**
		 * Funtion to get font size from attributes / global setting or default
		 * * pass "false" $g_key value if don't need global settings
		 */
		public function getFontSize( $attrs = [], $key = 'font', $screen = 'desktop', $g_key = 'font', $default_val = '', $important = false ) {

			$globals = isset( $attrs['global'] ) ? $attrs['global'] : ( class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [] );

			$important = 'mobile' === $screen ? true : $important;

			$has_important = false !== $important ? 'px !important;' : 'px;';

			$font_size = '';

			if ( false === $default_val && ( empty( $attrs ) || empty( $attrs[ $key ] ) || empty( $attrs[ $key ][ $screen ] ) ) && ( empty( $globals[ $g_key ] ) || empty( $globals[ $g_key ][ $screen ]['size'] ) ) ) {
				return $font_size;
			}

			if ( ! empty( $attrs[ $key ] ) && ! empty( $attrs[ $key ][ $screen ] ) && ! empty( $attrs[ $key ][ $screen ]['size'] ) ) {
				return 'font-size: ' . $attrs[ $key ][ $screen ]['size'] . $has_important;
			}

			if ( ! empty( $globals[ $g_key ] ) && ! empty( $globals[ $g_key ][ $screen ] ) && ! empty( $globals[ $g_key ][ $screen ]['size'] ) ) {
				return 'font-size: ' . $globals[ $g_key ][ $screen ]['size'] . $has_important;
			}

			if ( ! empty( $default_val ) ) {
				return ( false !== strpos( $default_val, 'font-size' ) ) ? $default_val : 'font-size: ' . $default_val . $has_important;
			} else if ( false === $default_val ) {
				return '';
			}

			return '';
		}

		public function verticalAlignment( $vAlign ) {
			if ( $vAlign ) {
				$vAlignValue = $vAlign === 'top' ? 'align-self: flex-start;width: 100%;' : '';
				$vAlignValue .= $vAlign === 'center' ? 'align-self: center;width: 100%;' : '';
				$vAlignValue .= $vAlign === 'bottom' ? 'align-self: flex-end;width: 100%;' : '';

				return $vAlignValue;
			}
		}

		public function backgroundCss( $attributes, $key, $screen, $important = false ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) ) {
				$gradient   = '';
				$image      = '';
				$color      = '';
				$repeat     = 'repeat';
				$size       = 'auto';
				$position   = 'top';
				$background = $attributes[ $key ][ $screen ];

				foreach ( $background as $key => $value ) {
					if ( $value ) {
						if ( $key === 'gradient' ) {
							$gradient = $value;
						} else if ( $key === 'image' ) {
							$image = $value;
						} else if ( $key === 'color' ) {
							$color = $value;
						} else if ( $key === 'repeat' ) {
							if ( ! empty( $value ) ) {
								$repeat = $value;
							}
						} else if ( $key === 'size' ) {
							if ( ! empty( $value ) ) {
								$size = $value;
							}
						} else if ( $key === 'position' ) {
							if ( ! empty( $value ) ) {
								$position = ( $value[0] ? $value[0] * 100 : 0 ) . '% ' . ( $value[1] ? $value[1] * 100 : 0 ) . '%';
							}
						}
					}
				}

				if ( $image && $gradient ) {
					$css .= sprintf( "background: %s url(%s) %s / %s %s, %s", $color, $image['url'], $position, $size, $repeat, $gradient . ( $important ? ' !important;' : ';' ) );
					$css .= sprintf( "background-repeat: %s", $repeat . ( $important ? ' !important;' : ';' ) );
					$css .= sprintf( "background-size: %s", $size . ( $important ? ' !important;' : ';' ) );
					$css .= sprintf( "background-position: %s", $position . ( $important ? ' !important;' : ';' ) );
				} else if ( $image ) {
					$css .= sprintf( "background: %s url(%s) %s / %s %s", $color, $image['url'], $position, $size, $repeat . ( $important ? ' !important;' : ';' ) );
					$css .= sprintf( "background-repeat: %s", $repeat . ( $important ? ' !important;' : ';' ) );
					$css .= sprintf( "background-size: %s", $size . ( $important ? ' !important;' : ';' ) );
					$css .= sprintf( "background-position: %s", $position . ( $important ? ' !important;' : ';' ) );
				} else if ( $gradient ) {
					$css .= sprintf( "background: %s", $gradient . ( $important ? ' !important;' : ';' ) );
				} else {
					if ( ! empty( $color ) ) {
						$css .= sprintf( "background: %s", $color . ( $important ? ' !important;' : ';' ) );
					}
				}

			}

			return $css;
		}


		public function getAlignment( $attributes, $key, $screen, $important = false ) {
			if ( ! isset( $attributes[ $key ] ) ) {
				return 'margin-left: auto' . ( $important ? ' !important;' : ';' ) . ' margin-right:auto' . ( $important ? ' !important;' : ';' );
			}
			if ( isset( $attributes[ $key ][ $screen ] ) ) {
				switch ( $attributes[ $key ][ $screen ] ) {
					case 'center':
						return 'margin: auto' . ( $important ? ' !important;' : ';' );
					case 'left':
						return 'margin-right: auto' . ( $important ? ' !important;' : ';' ) . 'margin-left:0' . ( $important ? ' !important;' : ';' );
					case 'right':
						return 'margin-left: auto' . ( $important ? ' !important;' : ';' ) . 'margin-right:0' . ( $important ? ' !important;' : ';' );
					default:
						return 'margin: auto' . ( $important ? ' !important;' : ';' );
				}
			}
		}

		public function getColor( $attributes, $key = '', $screen = 'desktop', $g_key = 'color', $important = false, $default = '' ) {
			$css = '';

			$globals = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [];

			if ( $key !== '' && isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && ! empty( $attributes[ $key ][ $screen ] ) ) {
				$css .= 'color: ' . $attributes[ $key ][ $screen ] . ( $important ? ' !important;' : ';' );
			}

			if ( empty( $css ) && isset( $globals[ $g_key ] ) && isset( $globals[ $g_key ][ $screen ] ) && ! empty( $globals[ $g_key ][ $screen ] ) ) {
				$css .= 'color: ' . $globals[ $g_key ][ $screen ] . ( $important ? ' !important;' : ';' );
			}

			if ( empty( $css ) ) {
				return $default;
			}

			return $css;
		}

		public function getWidth( $attributes, $key, $screen, $important = false ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && isset( $attributes[ $key ][ $screen ]['value'] ) ) {
				$css .= 'width: ' . $attributes[ $key ][ $screen ]['value'] . ( isset( $attributes[ $key ][ $screen ]['unit'] ) ? $attributes[ $key ][ $screen ]['unit'] : 'px' ) . ( $important ? ' !important;' : ';' );
			}

			return $css;
		}

		public function textAlign( $attributes, $key, $screen, $important = false, $default = '' ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && ! empty( $attributes[ $key ][ $screen ] ) ) {
				$css .= 'text-align: ' . $attributes[ $key ][ $screen ] . ( $important ? ' !important;' : ';' );
			}

			if ( empty( $css ) ) {
				return $default;
			}

			return $css;
		}

		/**
		 * Get color CSS
		 *
		 * @param array $attributes block attributes
		 * @param string $key color key
		 * @param string $screen screen value
		 * @param boolean $important include important in css property
		 *
		 * @return string css
		 */
		public function getBgColor( $attributes, $key, $screen, $g_key = 'buttonBackground', $important = false, $default = '' ) {
			$css = '';

			$globals = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [];

			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && ! empty( $attributes[ $key ][ $screen ] ) ) {
				if ( is_array( $attributes[ $key ][ $screen ] ) ) {
					foreach ( $attributes[ $key ][ $screen ] as $attr_key => $value ) {
						if ( ! empty( $value ) ) {
							$css .= 'background-' . $attr_key . ': ' . $value . ( $important ? ' !important;' : ';' );
						}
					}
				} else {
					$css .= 'background-color: ' . $attributes[ $key ][ $screen ] . ( $important ? ' !important;' : ';' );
				}
			}
			if ( empty( $css ) && isset( $globals[ $g_key ] ) && isset( $globals[ $g_key ][ $screen ] ) && isset( $globals[ $g_key ][ $screen ]['color'] ) && ! empty( $globals[ $g_key ][ $screen ]['color'] ) ) {
				$css .= 'background-color: ' . $globals[ $g_key ][ $screen ]['color'] . ( $important ? ' !important;' : ';' );
			}
			if ( empty( $css ) ) {
				return $default;
			}

			return $css;
		}

		public function getLineHeight( $attributes, $key, $screen, $important = false ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) ) {
				if ( isset( $attributes[ $key ][ $screen ]['value'] ) && ! empty( $attributes[ $key ][ $screen ]['value'] ) ) {
					$unit = '';
					$css  .= 'line-height: ' . $attributes[ $key ][ $screen ]['value'] . $unit . ( $important ? ' !important;' : ';' );
				}
			}

			return $css;
		}

		public function replaceIfEmpty( $value, $alternate ) {
			if ( empty( $value ) ) {
				return $alternate;
			}

			return $value;
		}

		public function handleAutoWidth( $attributes, $autoWidthKey, $widthKey, $screen, $g_key = [ 'buttonAuto', 'buttonSize' ], $important = false, $subAttr = '', $default_val = '' ) {
			$css = '';

			$globals = class_exists( 'BWFCRM_Block_Editor' ) ? BWFCRM_Block_Editor::$global_settings : [];

			$autoWidthKeyVal = $attributes[ $autoWidthKey ] ?? '';
			$widthKeyVal     = $attributes[ $widthKey ] ?? '';

			if ( empty( $autoWidthKeyVal ) && ! empty( $g_key ) ) {
				$autoWidthKeyVal = $globals[ $g_key[0] ] ?? '';
			}

			if ( empty( $widthKeyVal ) && ! empty( $g_key ) ) {
				$widthKeyVal = $globals[ $g_key[1] ] ?? '';
			}

			$autoWidth = $autoWidthKeyVal;
			$width     = $widthKeyVal;

			if ( $screen === 'desktop' && ( ! isset( $autoWidth ) || ! isset( $autoWidth[ $screen ] ) ) ) {
				return $css;
			}
			if ( $important && isset( $autoWidth[ $screen ] ) && $autoWidth[ $screen ] ) {
				return 'width: auto !important; max-width: 100%;';
			}

			if ( isset( $width[ $screen ] ) && ! ( isset( $autoWidth[ $screen ] ) && $autoWidth[ $screen ] ) ) {

				if ( ! empty( $subAttr ) ) {
					$css .= 'width: ' . $width[ $screen ][ $subAttr ] . ( $important ? '% !important;' : '%;' );
				} else {
					$css .= 'width: ' . $width[ $screen ] . ( $important ? '% !important;' : '%;' );
				}
			}

			return empty( $css ) ? $default_val : $css;
		}

		public function getWidthValue( $attributes, $autoWidthKey, $widthKey, $screen, $subAttr = '' ) {
			$css = 'auto';
			if ( ! isset( $attributes[ $autoWidthKey ] ) || ! isset( $attributes[ $autoWidthKey ][ $screen ] ) ) {
				return $css;
			}

			if ( isset( $attributes[ $widthKey ][ $screen ] ) && $attributes[ $autoWidthKey ][ $screen ] === false ) {
				if ( ! empty( $subAttr ) ) {
					$css = $attributes[ $widthKey ][ $screen ][ $subAttr ] . '%';
				} else {
					$css = $attributes[ $widthKey ][ $screen ] . '%;';
				}
			}

			return $css;
		}

		public function getProperty( $prop, $attributes, $key, $screen, $important = false, $default = '' ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && ! empty( $attributes[ $key ][ $screen ] ) ) {
				$css .= $prop . ': ' . $attributes[ $key ][ $screen ] . ( $important ? ' !important;' : ';' );
			}
			if ( empty( $css ) ) {
				return $default;
			}

			return $css;
		}

		public function getBold( $attributes, $key, $screen, $important = false, $default = '' ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && $attributes[ $key ][ $screen ] === true ) {
				$css .= 'font-weight: 600' . ( $important ? ' !important;' : ';' );
			}
			if ( empty( $css ) ) {
				return $default;
			}

			return $css;

		}

		public function getItalic( $attributes, $key, $screen, $important = false, $default = '' ) {
			$css = '';
			if ( isset( $attributes[ $key ] ) && isset( $attributes[ $key ][ $screen ] ) && $attributes[ $key ][ $screen ] === true ) {
				$css .= 'font-style: italic' . ( $important ? ' !important;' : ';' );
			}
			if ( empty( $css ) ) {
				return $default;
			}

			return $css;

		}

		public function getVisibilityCss( $attributes, $screen ) {
			$isHidden = isset( $attributes['isHidden'] ) && isset( $attributes['isHidden'][ $screen ] ) && $attributes['isHidden'][ $screen ] === true;
			$css      = '';
			if ( $screen === 'desktop' ) {
				if ( $isHidden ) {
					$css .= 'display: none; mso-hide: all;max-height: 0px;overflow: hidden;';
				}
			} else {
				$css .= 'mso-hide: unset !important;';
				if ( $isHidden ) {
					$css .= 'display: none !important;mso-hide: all !important;max-height: 0px !important;overflow: hidden !important;';
				} else {
					$css .= 'display: table-cell !important;';
					if ( $isHidden ) {
						$css .= 'max-height: unset !important;overflow: unset !important;';
					}
				}
			}

			return $css;
		}

	}
}


/**
 * Access for css functions
 *
 * @return bool True if the pattern was registered with success and false otherwise.
 */
if ( ! function_exists( 'bwf_css' ) ) {
	function bwf_css() {
		return BWF_EMAILBLOCKS_CSS::get_instance();
	}
}
