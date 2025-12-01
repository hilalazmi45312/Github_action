/* global efw_enhanced_select_params */

jQuery( function ( $ ) {
	'use strict' ;

	try {
		$( document.body ).on( 'efw-enhanced-init' , function () {
			if ( $( 'select.efw_select2' ).length ) {
				//Select2 with customization
				$( 'select.efw_select2' ).each( function () {
					var select2_args = {
						allowClear : $( this ).data( 'allow_clear' ) ? true : false ,
						placeholder : $( this ).data( 'placeholder' ) ,
						minimumResultsForSearch : 10 ,
					} ;
					$( this ).select2( select2_args ) ;
				} ) ;
			}
			if ( $( 'select.efw_select2_search' ).length ) {
				//Multiple select with ajax search
				$( 'select.efw_select2_search' ).each( function () {
					var select2_args = {
						allowClear : $( this ).data( 'allow_clear' ) ? true : false ,
						placeholder : $( this ).data( 'placeholder' ) ,
						minimumInputLength : $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : 3 ,
						escapeMarkup : function ( m ) {
							return m ;
						} ,
						ajax : {
							url : efw_enhanced_select_params.ajaxurl ,
							dataType : 'json' ,
							delay : 250 ,
							data : function ( params ) {
								return {
									term : params.term ,
									action : $( this ).data( 'action' ) ? $( this ).data( 'action' ) : '' ,
									exclude_global_variable : $( this ).data( 'exclude-global-variable' ) ? $( this ).data( 'exclude-global-variable' ) : 'no' ,
									efw_security : $( this ).data( 'nonce' ) ? $( this ).data( 'nonce' ) : efw_enhanced_select_params.search_nonce ,
								} ;
							} ,
							processResults : function ( data ) {
								var terms = [ ] ;
								if ( data ) {
									$.each( data , function ( id , term ) {
										terms.push( {
											id : id ,
											text : term
										} ) ;
									} ) ;
								}
								return {
									results : terms
								} ;
							} ,
							cache : true
						}
					} ;

					$( this ).select2( select2_args ) ;
				} ) ;
			}

			if ( $( '.efw_from_date' ).length ) {
				$( '.efw_from_date' ).each( function ( ) {

					$( this ).datepicker( {
						altField : $( this ).next( ".efw_alter_datepicker_value" ) ,
						altFormat : 'yy-mm-dd' ,
						changeMonth : true ,
						changeYear : true ,
						onClose : function ( selectedDate ) {
							var maxDate = new Date( Date.parse( selectedDate ) ) ;
							maxDate.setDate( maxDate.getDate() ) ;
							$( '.efw_to_date' ).datepicker( 'option' , 'minDate' , maxDate ) ;
						}
					} ) ;

				} ) ;
			}

			if ( $( '.efw_to_date' ).length ) {
				$( '.efw_to_date' ).each( function ( ) {

					$( this ).datepicker( {
						altField : $( this ).next( ".efw_alter_datepicker_value" ) ,
						altFormat : 'yy-mm-dd' ,
						changeMonth : true ,
						changeYear : true ,
						onClose : function ( selectedDate ) {
							$( '.efw_from_date' ).datepicker( 'option' , 'maxDate' , selectedDate ) ;
						}
					} ) ;

				} ) ;
			}

			if ( $( '.efw_datepicker' ).length ) {
				$( '.efw_datepicker' ).on( 'change' , function ( ) {
					if ( $( this ).val() === '' ) {
						$( this ).next( ".efw_alter_datepicker_value" ).val( '' ) ;
					}
				} ) ;

				$( '.efw_datepicker' ).each( function ( ) {
					$( this ).datepicker( {
						altField : $( this ).next( ".efw_alter_datepicker_value" ) ,
						altFormat : 'yy-mm-dd' ,
						changeMonth : true ,
						changeYear : true
					} ) ;
				} ) ;
			}


			// Color picker
			$( '.colorpick' )

					.iris( {
						change : function ( event , ui ) {
							$( this ).parent().find( '.colorpickpreview' ).css( { backgroundColor : ui.color.toString() } ) ;
						} ,
				hide : true ,
				border : true
					} )

					.on( 'click focus' , function ( event ) {
						event.stopPropagation() ;
						$( '.iris-picker' ).hide() ;
						$( this ).closest( 'td' ).find( '.iris-picker' ).show() ;
						$( this ).data( 'original-value' , $( this ).val() ) ;
					} )

					.on( 'change' , function () {
						if ( $( this ).is( '.iris-error' ) ) {
							var original_value = $( this ).data( 'original-value' ) ;

							if ( original_value.match( /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/ ) ) {
								$( this ).val( $( this ).data( 'original-value' ) ).change() ;
							} else {
								$( this ).val( '' ).change() ;
							}
						}
					} ) ;

			$( 'body' ).on( 'click' , function () {
				$( '.iris-picker' ).hide() ;
			} ) ;

		} ) ;

		$( document.body ).trigger( 'efw-enhanced-init' ) ;
	} catch ( err ) {
		window.console.log( err ) ;
	}

} ) ;
