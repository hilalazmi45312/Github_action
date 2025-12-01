jQuery( function ( $ ) {
	'use strict';

	var EFW_Frontend_Scripts = {
		$order_review: $( '#order_review' ),
		$checkout_form : $( 'form.checkout' ) ,
		init : function ( ) {
			EFW_Frontend_Scripts.add_fee_for_gateway() ;
			$( document ).on( 'payment_method_selected' , this.add_fee_for_gateway ) ;
			$( document ).on( 'payment_method_selected' , this.add_fee_in_pay_for_order ) ;
			$( document ).on( 'change' , 'input, wcva_attribute_radio' , this.variation_notice ) ;
			$( document ).on( 'click' , '.efw-fee-desc-popup' , this.fee_desc_popup ) ;
			$( document ).on( 'click' , '.efw-fee-desc-rule-popup' , this.fee_desc_rule_popup ) ;
			$( document ).on( 'click' , '.efw-gateway-fee-desc-popup' , this.fee_gateway_desc_popup ) ;
			$( document ).on( 'click' , '.efw-order-fee-desc-popup' , this.fee_order_desc_popup ) ;
			$( document ).on( 'click' , '.efw-shipping-fee-desc-popup' , this.fee_shipping_desc_popup ) ;
			$( document ).on( 'click' , '.efw-combined-fee-desc-popup' , this.fee_combined_desc_popup ) ;
			$( '#wc-bookings-booking-form' ).on( 'change' , 'input, select' , this.display_booking_cost ) ;
		} ,
		add_fee_for_gateway : function (e) {
			if ( (efw_frontend_param.is_checkout) && 'yes' == efw_frontend_param.is_gateway_fee_enabled ) {
				EFW_Frontend_Scripts.block( EFW_Frontend_Scripts.$checkout_form ) ;

				var gateway_id = $( '.payment_methods input[name="payment_method"]:checked' ).val();

				var data = ( {
					action : 'efw_automatic_subscription' ,
					gatewayid : gateway_id ,
					efw_security : efw_frontend_param.fee_nonce
				} ) ;
				
				$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
					if ( true === response.success ) {
						$(document.body).trigger('update_checkout');
					} else {
						window.alert( response.data.error ) ;
					}
					EFW_Frontend_Scripts.unblock( EFW_Frontend_Scripts.$checkout_form ) ;
				} ) ;
			}
		} ,
		add_fee_in_pay_for_order : function (e) {
			if ( (efw_frontend_param.is_pay_for_order_page) && 'yes' == efw_frontend_param.is_gateway_fee_enabled ) {
				EFW_Frontend_Scripts.block( EFW_Frontend_Scripts.$order_review ) ;

				var gateway_id = $( '.payment_methods input[name="payment_method"]:checked' ).val();

				var data = ( {
					action : 'efw_fee_in_pay_for_order' ,
					gatewayid : gateway_id ,
					pay_for_order : efw_frontend_param.is_pay_for_order_page,
					order_id : efw_frontend_param.order_id,
					efw_security : efw_frontend_param.fee_nonce
				} ) ;
				
				$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
					if ( true === response.success ) {
						if( '' != response.data.pay_for_order){
							$('.shop_table').replaceWith(response.data.pay_for_order);
						}
					} else {
						window.alert( response.data.error ) ;
					}
					EFW_Frontend_Scripts.unblock( EFW_Frontend_Scripts.$order_review ) ;
				} ) ;
			}
		} ,
		variation_notice : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ;
			if ( efw_frontend_param.is_product && 'yes' == efw_frontend_param.is_enabled ) {
				if ( $( $this ).closest( 'div.single_variation_wrap' ).find( 'input:hidden[name=variation_id], input.variation_id' ).length ) {
					var variationid = $( this ).closest( 'div.single_variation_wrap' ).find( 'input:hidden[name=variation_id], input.variation_id' ).val() ;
					if ( variationid === '' || variationid === 0 || variationid === undefined ) {
						return false ;
					} else {
						EFW_Frontend_Scripts.block( $( this ) ) ;
						var data = ( {
							action : 'efw_variation_notice' ,
							variationid : variationid ,
							efw_security : efw_frontend_param.fee_nonce
						} ) ;
						$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
							if ( true === response.success ) {
								if ( response.data.html ) {
									$( $this ).closest( 'div.single_variation_wrap' ).find( '.efw-variation-price-table' ).html( response.data.html ) ;
								}
							} else {
								window.alert( response.data.error ) ;
							}
							EFW_Frontend_Scripts.unblock( $( this ) ) ;
						} ) ;
					}
				}
			}
		} ,
		fee_desc_popup : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ,
					$product_id = $this.data( 'product_id' ) ;

			var data = ( {
				action : 'efw_fee_desc_popup' ,
				product_id : $product_id ,
				efw_security : efw_frontend_param.fee_desc_popup_nonce
			} ) ;
			EFW_Frontend_Scripts.block( $this ) ;
			$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
				if ( true === response.success ) {
					EFW_Frontend_Scripts.unblock( $this ) ;
					if ( response.data.html ) {
						$this.after( response.data.html ) ;
						$this.closest( 'div' ).find( '#efw_fee_desc_popup' ).modal() ;
					}
				} else {
					window.alert( response.data.error ) ;
					EFW_Frontend_Scripts.unblock( $this ) ;
				}
			} ) ;
			return false ;
		} ,
		fee_desc_rule_popup : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ,
					$product_id = $this.data( 'product_id' ) ,
					$rule_id = $this.data( 'rule_id' ) ;

			var data = ( {
				action : 'efw_fee_desc_rule_popup' ,
				product_id : $product_id ,
				rule_id : $rule_id ,
				efw_security : efw_frontend_param.fee_desc_rule_popup_nonce
			} ) ;
			EFW_Frontend_Scripts.block( $this ) ;
			$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
				if ( true === response.success ) {
					EFW_Frontend_Scripts.unblock( $this ) ;
					if ( response.data.html ) {
						$this.after( response.data.html ) ;
						$this.closest( 'div' ).find( '#efw_fee_desc_rule_popup' ).modal() ;
					}
				} else {
					window.alert( response.data.error ) ;
					EFW_Frontend_Scripts.unblock( $this ) ;
				}
			} ) ;
			return false ;
		} ,
		fee_gateway_desc_popup : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ;
			var data = ( {
				action : 'efw_fee_gateway_desc_popup' ,
				gateway_id : $this.data( 'gateway_id' ) ,
				efw_security : efw_frontend_param.fee_gateway_desc_popup_nonce
			} ) ;
			EFW_Frontend_Scripts.block( $this ) ;
			$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
				if ( true === response.success ) {
					EFW_Frontend_Scripts.unblock( $this ) ;
					if ( response.data.html ) {
						$this.after( response.data.html ) ;
						$this.closest( 'div' ).find( '#efw_gateway_fee_desc_popup' ).modal() ;
					}
				} else {
					window.alert( response.data.error ) ;
					EFW_Frontend_Scripts.unblock( $this ) ;
				}
			} ) ;
			return false ;
		} ,
		fee_order_desc_popup : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ;
			var data = ( {
				action : 'efw_fee_order_desc_popup' ,
				efw_security : efw_frontend_param.fee_order_desc_popup_nonce
			} ) ;
			EFW_Frontend_Scripts.block( $this ) ;
			$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
				if ( true === response.success ) {
					EFW_Frontend_Scripts.unblock( $this ) ;
					if ( response.data.html ) {
						$this.after( response.data.html ) ;
						$this.closest( 'div' ).find( '#efw_order_fee_desc_popup' ).modal() ;
					}
				} else {
					window.alert( response.data.error ) ;
					EFW_Frontend_Scripts.unblock( $this ) ;
				}
			} ) ;
			return false ;
		} ,
		fee_combined_desc_popup : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ;
			var data = ( {
				action : 'efw_fee_combined_desc_popup' ,
				fee_detail : $(this).data('fee_details') ,
				efw_security : efw_frontend_param.combined_fee_desc_popup_nonce
			} ) ;
			EFW_Frontend_Scripts.block( $this ) ;
			$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
				if ( true === response.success ) {
					EFW_Frontend_Scripts.unblock( $this ) ;
					if ( response.data.html ) {
						$this.after( response.data.html ) ;
						$this.closest( 'div' ).find( '#efw_combined_fee_desc_popup' ).modal() ;
					}
				} else {
					window.alert( response.data.error ) ;
					EFW_Frontend_Scripts.unblock( $this ) ;
				}
			} ) ;
			return false ;
		} ,
		fee_shipping_desc_popup : function ( e ) {
			e.preventDefault() ;
			var $this = $( e.currentTarget ) ;
			var data = ( {
				action : 'efw_fee_shipping_desc_popup' ,
				shipping_id : $this.data( 'shipping_id' ) ,
				efw_security : efw_frontend_param.fee_shipping_desc_popup_nonce
			} ) ;
			EFW_Frontend_Scripts.block( $this ) ;
			$.post( efw_frontend_param.ajaxurl , data , function ( response ) {
				if ( true === response.success ) {
					EFW_Frontend_Scripts.unblock( $this ) ;
					if ( response.data.html ) {
						$this.after( response.data.html ) ;
						$this.closest( 'div' ).find( '#efw_shipping_fee_desc_popup' ).modal() ;
					}
				} else {
					window.alert( response.data.error ) ;
					EFW_Frontend_Scripts.unblock( $this ) ;
				}
			} ) ;
			return false ;
		} ,
		display_booking_cost : function ( e ) {
            e.preventDefault() ;
            var form = $( this ).closest( 'form' ) ;
            EFW_Frontend_Scripts.block( form ) ;
            var data = {
                action : 'efw_display_booking_cost' ,
                form : form.serialize() ,
				efw_security : efw_frontend_param.booking_nonce
            } ;
            $.ajax( {
                url : efw_frontend_param.ajaxurl ,
                data : data ,
                dataType : 'JSON' ,
                type : 'post' ,
                success : function ( response ) {
                    if ( true == response.success ) {
                        form.find( '.efw-product-fee-table' ).html( response.data.html ) ;
                        form.find( '.single_add_to_cart_button' ).removeClass( 'disabled' ) ;
                    }

                } ,
                complete : function () {
                    EFW_Frontend_Scripts.unblock( form ) ;
                }
            } ) ;

        },
		block : function ( id ) {
			$( id ).block( {
				message : null ,
				overlayCSS : {
					background : '#fff' ,
					opacity : 0.6
				}
			} ) ;
		} ,
		unblock : function ( id ) {
			$( id ).unblock() ;
		} ,
	} ;
	EFW_Frontend_Scripts.init( ) ;
} ) ;
