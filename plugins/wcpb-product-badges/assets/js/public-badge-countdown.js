jQuery( document ).ready( function( $ ) {

	// Translation

	const { __, _x, _n, _nx, sprintf } = wp.i18n;

	// Badge countdown

	function badgeCountdown() {

		$( '.wcpb-product-badges-badge .wcpb-product-badges-badge-countdown' ).each( function() {

			var countdownElement = $( this );
			countdownElement.hide(); // Even though the badge is hidden until load the page loads before the first interval and so a blank badge appears for a few milliseconds, so we hide this until the first interval is run and then unhide it

			var countdownTo = new Date( countdownElement.attr( 'data-countdown-to' ) ).getTime();
			var countdownTextBefore = countdownElement.attr( 'data-countdown-text-before' );
			var countdownTextAfter = countdownElement.attr( 'data-countdown-text-after' );

			var countdownInterval = setInterval( function() {

				var countdownNow = new Date().getTime();
				var countdownDistance = countdownTo - countdownNow;
				var countdownDays = Math.floor( countdownDistance / ( 1000 * 60 * 60 * 24 ) );
				var countdownHours = Math.floor( ( countdownDistance % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) );
				var countdownMinutes = Math.floor( ( countdownDistance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
				var countdownSeconds = Math.floor( ( countdownDistance % ( 1000 * 60 ) ) / 1000 );
				var	countdownString = '';

				if ( countdownTextBefore !== '' ) {

					countdownString = countdownString + '<div class="wcpb-product-badges-badge-countdown-text-before">' + countdownTextBefore + '</div>';

				}

				countdownString = countdownString + '<div class="wcpb-product-badges-badge-countdown-parts">'; // Countdown start div

				if ( countdownDays > 0 ) {

					countdownString = countdownString + '<span class="wcpb-product-badges-badge-countdown-part wcpb-product-badges-badge-countdown-part-days">' + countdownDays + __( 'd', 'wcpb-product-badges' ) + '</span>';

				}

				if ( countdownHours > 0 ) {

					countdownString = countdownString + '<span class="wcpb-product-badges-badge-countdown-part wcpb-product-badges-badge-countdown-part-hours">' + countdownHours + __( 'h', 'wcpb-product-badges' ) + '</span>';

				}

				if ( countdownMinutes > 0 ) {

					countdownString = countdownString + '<span class="wcpb-product-badges-badge-countdown-part wcpb-product-badges-badge-countdown-part-minutes">' + countdownMinutes + __( 'm', 'wcpb-product-badges' ) + '</span>';

				}

				countdownString = countdownString + '<span class="wcpb-product-badges-badge-countdown-part wcpb-product-badges-badge-countdown-part-seconds">' + countdownSeconds + __( 's', 'wcpb-product-badges' ) + '</span>'; // This doesn't have a condition otherwise it would hide at 0 seconds when countdown still running (e.g. 1 day 2 hours)

				countdownString = countdownString + '</div>'; // Countdown end div

				if ( countdownTextAfter !== '' ) {

					countdownString = countdownString + '<div class="wcpb-product-badges-badge-countdown-text-after">' + countdownTextAfter + '</div>';

				}

				if ( countdownDistance > 0 ) {

					countdownElement.html( countdownString );
					countdownElement.show(); // See comment on countdownElement.hide() above why this exists

				} else {

					clearInterval( countdownInterval );
					countdownElement.remove();

				}

			}, 1000 );

		});

	}

	badgeCountdown();

});