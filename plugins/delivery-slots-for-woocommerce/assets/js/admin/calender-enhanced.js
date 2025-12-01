/* global dey_calender_params */

jQuery( function ( $ ) {
	'use strict' ;
	try {
		$( document.body ).on( 'dey-calender-enhanced-init' , function ( ) {

			if ( $( '.dey_calender' ).length ) {

				$( '.dey_calender' ).each( function ( ) {
					var calender = new FullCalendar.Calendar( this , {
						timeZone : 'UTC' ,
						initialView : 'dayGridMonth' ,
						dayMaxEventRows : true ,
						locales : [ dey_calender_params ] ,
						locale : dey_calender_params.code ,
						views : {
							timeGrid : {
								dayMaxEventRows : 3 // adjust to 6 only for timeGridWeek/timeGridDay
							}
						} ,
						headerToolbar : {
							left : 'prev,next today' ,
							center : 'title' ,
							right : 'dayGridMonth,timeGridWeek,timeGridDay'
						} ,
						footerToolbar : {
							left : 'prev,next today' ,
							center : 'title' ,
							right : 'dayGridMonth,timeGridWeek,timeGridDay'
						} ,
						events : function ( fetchInfo , successCallback , failureCallback ) {
							var data = ( {
								action : 'dey_calender_events' ,
								start : fetchInfo.startStr ,
								end : fetchInfo.endStr ,
								type : $( '.dey-delivery-calender-type' ).val( ) ,
								dey_security : dey_calender_params.calender_events_nonce ,
							} ) ;
							$.post( ajaxurl , data , function ( res ) {
								if ( true === res.success ) {
									successCallback( res.data.events ) ;
								} else {
									failureCallback( res.data.error ) ;
								}

							} ) ;
						} ,
						eventDidMount : function ( info ) {
							info.el.style.cursor = 'pointer' ;
						} ,
						eventClick : function ( info ) {
							info.el.style.border = '#dddddd' ;

							if ( null === info.el.getAttribute( 'data-hasqtip' ) ) {
								dey_block( info.el ) ;

								var data = ( {
									action : 'dey_calender_event_data' ,
									event_id : info.event.id ,
									type : $( '.dey-delivery-calender-type' ).val( ) ,
									dey_security : dey_calender_params.calender_events_nonce ,
								} ) ;

								$.post( ajaxurl , data , function ( res ) {
									var content = '' ;
									if ( true === res.success ) {
										var content = res.data.event ;
									}

									$( info.el ).qtip( {
										content : {
											text : content ,
											button : 'Close' ,
										} , show : {
											event : 'click' ,
											solo : true
										} , position : {
											my : 'bottom right' , // this is for the botton v shape icon position.
											at : 'top right' // this is for the content box position
										} ,
										hide : 'unfocus' ,
										style : {
											classes : 'qtip-light qtip-shadow'
										}
									} ) ;

									$( info.el ).trigger( 'click' ) ;

								} ) ;
							} else {
								$( info.el ).trigger( 'click' ) ;
							}

							dey_unblock( info.el ) ;

						} ,
						dayCellDidMount : function ( info ) {

							var year = info.date.getFullYear( ) ,
									month = ( '0' + ( info.date.getMonth( ) + 1 ) ).slice( -2 ) ,
									date = ( '0' + info.date.getDate( ) ).slice( -2 ) ,
									current_month = month + '-' + date ,
									current_date = year + '-' + current_month ;
							
							if ( dey_calender_params.holidays[current_date] ) {
								info.el.classList.add( "dey-calender-holiday" ) ;
								info.el.style.background = '#00aba9' ;
								info.el.title = dey_calender_params.holidays[current_date].label ;
							} else if ( dey_calender_params.holidays[ current_month] ) {
								info.el.classList.add( "dey-calender-holiday" ) ;
								info.el.style.background = '#00aba9' ;
								info.el.title = dey_calender_params.holidays[ current_month].label ;
							}

						}

					} ) ;
					calender.render( ) ;
				} ) ;
			}

		} ) ;
		$( document.body ).trigger( 'dey-calender-enhanced-init' ) ;
	} catch ( err ) {
		window.console.log( err ) ;
	}

	/**
	 * Block the element.
	 * 
	 * @param {Sting} id
	 * @returns {Void}
	 */
	var dey_block = function ( id ) {
		if ( !dey_is_blocked( id ) ) {
			$( id ).addClass( 'processing' ).block( {
				message : null ,
				overlayCSS : {
					background : '#fff' ,
					opacity : 0.7
				}
			} ) ;
		}
	}

	/**
	 * Is blocked current element?.
	 * 
	 * @param {Sting} id
	 * @returns {Boolean}
	 */
	var dey_is_blocked = function ( id ) {
		return $( id ).is( '.processing' ) || $( id ).parents( '.processing' ).length ;
	}

	/**
	 * Unblock the element.
	 * 
	 * @param {Sting} id
	 * @returns {Void}
	 */
	var dey_unblock = function ( id ) {
		$( id ).removeClass( 'processing' ).unblock() ;
	}

} ) ;
