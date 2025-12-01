<?php namespace MeowCrew\SubscriptionsDiscounts\Integrations\Plugins\AllProductsForSubscriptions;

use MeowCrew\SubscriptionsDiscounts\Core\ServiceContainerTrait;
use MeowCrew\SubscriptionsDiscounts\DiscountsManager;
use MeowCrew\SubscriptionsDiscounts\Frontend\CartManager;
use WC_Product;
use WCS_ATT_Product_Schemes;
use WCS_ATT_Scheme;

class AllProductForSubscriptions {
	
	const SETTING_ENABLE_KEY = 'enable_all_products_for_subscriptions_addon';
	
	use ServiceContainerTrait;
	
	protected static $adjustedSchemesKeys = [];
	
	public function __construct() {
		
		if ( $this->isActive() ) {
			$this->run();
		}
	}
	
	public function getName() {
		return 'All Products For Subscriptions';
	}
	
	/**
	 * Whether addon is active or not
	 *
	 * @return bool
	 */
	public function isActive() {
		return $this->getContainer()->getSettings()->get( self::SETTING_ENABLE_KEY, 'yes' ) === 'yes';
	}
	
	public function run() {
		add_action( 'subscription_discounts/frontend/after_discounts_table_rendered', array(
			$this,
			'renderPlansTables',
		), 10, 10 );
		
		add_filter( 'subscription_discounts/frontend/supported_variable_product_types', function ( $types ) {
			$types[] = 'variable';
			$types[] = 'variation';
			
			return $types;
		} );
		
		add_filter( 'subscription_discounts/frontend/supported_simple_product_types', function ( $types ) {
			$types[] = 'simple';
			$types[] = 'bundle';
			
			return $types;
		} );
		
		// Disable regular first Payment Discount handling
		add_filter( 'subscription_discounts/first_payment_discount/update_price', function ( $update, $cartItem ) {
			if ( ! empty( $cartItem['wcsatt_data'] ) ) {
				return false;
			}
			
			return $update;
		}, 10, 2 );
  
		
		add_filter( 'subscription_discounts/cart/next_renewal_discount_price',
			function ( $newPrice, $productId, $discounts, $discountType, $cartItem ) {
				
				if ( ! class_exists( 'WCS_ATT_Product_Schemes' ) ) {
					return $newPrice;
				}
				
				$subscriptionScheme = WCS_ATT_Product_Schemes::get_subscription_scheme( $cartItem['data'], 'object' );
				
				if ( ! $subscriptionScheme ) {
					return $newPrice;
				}
				
				if ( ! $subscriptionScheme->get_discount() ) {
					return $newPrice;
				}
				
				if ( 'fixed' === $discountType ) {
					$newPrice = $discounts[2];
				} else {
					$newPrice = DiscountsManager::getPriceByPercentDiscount( $cartItem['data']->get_price(),
						$discounts[2] );
				}
	
				return $this->calculateOriginalPrice( $newPrice, $subscriptionScheme->get_discount() );
			}, 10, 5 );
		
		// Add first payment discount to subscription schemes
		add_filter( 'wcsatt_product_subscription_schemes', function ( $schemes, $product ) {
			
			foreach ( $schemes as $key => $scheme ) {
				
				/**
				 * Type hinting for PhpStorm
				 *
				 * @var WCS_ATT_Scheme $scheme
				 */
				$pricingRules     = CartManager::getProductDiscounts( $product->get_id() );
				$pricingRulesType = CartManager::getProductDiscountsType( $product->get_id() );
				
				
				$schemePrices = $scheme->get_prices( [
					'price'         => $product->get_price( 'edit' ),
					'regular_price' => $product->get_regular_price( 'edit' ),
					'sale_price'    => $product->get_sale_price( 'edit' ),
				] );
				
				$schemePrice = ! empty( $schemePrices['price'] ) ? (float) $schemePrices['price'] : null;
				
				// Save original price. If price is different from original - do not modify it anymore
				if ( empty( self::$adjustedSchemesKeys[ $key . '-' . $product->get_id() ] ) ) {
					self::$adjustedSchemesKeys[ $key . '-' . $product->get_id() ] = $schemePrice;
				} elseif ( self::$adjustedSchemesKeys[ $key . '-' . $product->get_id() ] !== $schemePrice ) {
					continue;
				}
				
				$originalProductPrice = $product->get_price( 'edit' );
				
				$newPrice = DiscountsManager::getPriceByRules( 1, $product->get_id(), 'no-tax', 'cart', $schemePrice,
					$pricingRules, $pricingRulesType );
				
				if ( false !== $newPrice ) {
					$scheme->set_pricing_mode( 'inherit' );
					$scheme->set_discount( $this->calculatePercentageDiscount( $originalProductPrice, $newPrice ) );
				}
			}
			
			return $schemes;
		}, 10, 2 );
		
	}
	
	public function deductPercentageDiscount( $price, $percentageDiscount ) {
		return $price + ( $price * $percentageDiscount / 100 );
	}
	
	public function calculatePercentageDiscount( $originalPrice, $discountedPrice ) {
		return $originalPrice > 0 ? ceil( 100 - ( $discountedPrice * 100 / $originalPrice ) ) : 0;
	}
	
	public function calculateOriginalPrice( $discountedPrice, $discountPercentage ) {
		return $discountedPrice / ( 1 - ( $discountPercentage / 100 ) );
	}
	
	public function renderPlansTables( WC_Product $product, $rules, $template ) {
		
		if ( class_exists( '\WCS_ATT_Product_Schemes' ) ) {
			$subscriptionSchemes = WCS_ATT_Product_Schemes::get_subscription_schemes( $product );
			$real_price          = $product->get_price();
			
			if ( ! empty( $subscriptionSchemes ) && is_array( $subscriptionSchemes ) ) {
				
				foreach ( $subscriptionSchemes as $key => $subscriptionPlan ) {
					
					$data = $subscriptionPlan->get_data();
					
					if ( ! empty( $data['price'] ) ) {
						$_real_price = floatval( $data['price'] );
					} elseif ( ! empty( $data['discount'] ) ) {
						$discount = floatval( $data['discount'] );
						
						$discountValue = ( $real_price / 100 ) * $discount;
						$_real_price   = $real_price - $discountValue;
					} else {
						$_real_price = $real_price;
					}
					?>
					<div style="display: none"
						 class="data-discounts-table-container data-discounts-table-container--apfs"
						 data-all-products-for-subscription="<?php echo esc_attr( $key ); ?>">
						<?php
							$this->getContainer()->getFileManager()->includeTemplate( 'frontend/' . $template, array(
								'price_rules'  => $rules,
								'real_price'   => $_real_price,
								'product_name' => $product->get_name(),
								'product_id'   => $product->get_id(),
								'product'      => $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product,
								'settings'     => $this->getContainer()->getSettings()->getAll(),
							) );
						?>
					</div>
					<script>
						jQuery(document).ready(function ($) {

							function showDiscountsTable() {
								let selectedSubscription = $('[name^=convert_to_sub_]').first().val();

								if ($('[name=subscribe-to-action-input]:checked').val() === 'yes') {
									$('.data-discounts-table-container').hide();
									$('[data-all-products-for-subscription=' + selectedSubscription + ']').show();
								}
							}

							$('[name^=convert_to_sub_]').on('change', function () {

								$('.data-discounts-table-container').hide();

								showDiscountsTable();
							});

							$('[name=subscribe-to-action-input]').on('change', function () {
								if ($('[name=subscribe-to-action-input]:checked').val() === 'no') {
									$('.data-discounts-table-container').hide();
								} else {
									showDiscountsTable()
								}
							}).trigger('change');

						});
					</script>
					<?php
				}
			}
		}
	}
}
