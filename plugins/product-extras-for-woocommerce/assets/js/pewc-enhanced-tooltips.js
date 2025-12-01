(function($) {
	$(document).ready(function() {
		
		var enhanced_tooltips = {

			init: function() {
				
				$( '.pewc-tooltip-button' ).on( 'click', this.open_tooltip );
				$( '.pewc-enhanced-close, pewc-enhanced-tooltip.active' ).on( 'click', this.close_tooltip );
				$( 'body' ).attr( 'data-overflow', $( 'body' ).css( 'overflow' ) );
				// 3.23.1, close the tooltip if user clicks outside the modal box
				$( document ).on( 'click', '.pewc-enhanced-tooltip.active', function( e ){
					if ( ! $( e.target ).closest( '.pewc-enhanced-tooltip-wrapper' ).length ) {
						enhanced_tooltips.close_tooltip();
					}
				});

			},

			open_tooltip: function() {
				var tooltip_id = $( this ).closest( '.pewc-item' ).attr( 'data-tooltip-id' );
				$( 'body' ).css( 'overflow', 'hidden' );
				$( '#pewc-enhanced-tooltip-' + tooltip_id ).addClass( 'active' );
			},

			close_tooltip: function() {
				$( '.pewc-enhanced-tooltip' ).removeClass( 'active' );
				$( 'body' ).css( 'overflow', $( 'body' ).attr( 'data-overflow' ) );
			}

		}

		enhanced_tooltips.init();

	});
})(jQuery);
