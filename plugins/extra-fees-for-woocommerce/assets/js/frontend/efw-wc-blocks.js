jQuery( function ( $ ) {
	'use strict';

	var wc_blocks_checkout = window.wc.blocksCheckout;
	var EFW_WC_Blocks_Script = {
		init : function ( ) {
			$( window ).on('load', function() {
				EFW_WC_Blocks_Script.add_fee_for_gateway();
		   	});

			$(document).ready(function() {
				EFW_WC_Blocks_Script.add_fee_for_gateway();
		   	});

			setTimeout(function(){
				EFW_WC_Blocks_Script.add_fee_for_gateway();
			}, 3000);

			$( document ).on( 'change' , '.wc-block-components-radio-control__input' , this.add_fee_for_gateway ) ;
		} ,
		add_fee_for_gateway : function () {
			if ( efw_blocks_param.is_checkout && 'yes' == efw_blocks_param.is_gateway_fee_enabled ) {
				var gateway_id = $('input[name="radio-control-wc-payment-method-options"]:checked').val();
				wc_blocks_checkout.extensionCartUpdate( {
					namespace: 'efw-add-gateway-fee',
					data: {
						action : 'add-fee',
						gateway_id : gateway_id
					},
				} );
			}
		} ,
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
	EFW_WC_Blocks_Script.init( ) ;
} ) ;
