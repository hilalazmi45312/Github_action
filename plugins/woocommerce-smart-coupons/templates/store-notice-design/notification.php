<?php
/**
 * Smart Coupons store notice design - Gift Box
 *
 * @author      StoreApps
 * @package     WooCommerce Smart Coupons/Templates
 *
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="sc_action_bar sc_container sc_hello sc_no_hide sc_bottom sc_no_icon sc_show sc_anim_no-anim_in" id="sc_action_bar_style_4" style="color: rgb(249, 249, 249); display: block;">
	<style type="text/css">

		/* Outer fixed container */
		.custom-banner-container {
			inset: 0 auto auto 0;
			bottom: 0;
			padding-bottom: 0.5rem;
			z-index: 999999;
		}

		@media (min-width: 640px) {
			/* For sm: */
			.custom-banner-container {
				padding-bottom: 1.25rem;
			}
		}

		/* Centering the content within max-width */
		.custom-banner-content {
			max-width: 1280px;
			padding: 0.5rem;
			margin: 0 auto;
		}

		@media (min-width: 640px) {
			.custom-banner-content {
				padding-left: 1.5rem;
				padding-right: 1.5rem;
			}
		}

		@media (min-width: 1024px) {
			.custom-banner-content {
				padding-left: 2rem;
				padding-right: 2rem;
			}
		}

		/* Inner banner styling */
		.custom-banner-inner {
			padding: 0.5rem;
			background-color: #4c51bf;
			border-radius: 0.5rem;
			box-shadow: 0px 10px 15px -3px rgba(0, 0, 0, 0.1), 0px 4px 6px -2px rgba(0, 0, 0, 0.05);
		}

		@media (min-width: 640px) {
		.custom-banner-inner {
			padding: 0.75rem;
		}
		}

		/* Flex container for the banner header */
		.custom-banner-header {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			justify-content: space-between;
		}

		/* Icon and text container */
		.custom-banner-icon-text {
			display: flex;
			align-items: center;
			flex: 1;
			min-width: 0;
		}

		.custom-icon {
			display: flex;
			padding: 0.5rem;
			background-color: #42389d!important;
			border-radius: 0.5rem;
			width: 1.5rem;
			height: 1.5rem;
		}

		.custom-icon-svg {
			width: 1.5rem;
			height: 1.5rem;
			color: #ffffff;
		}

		.custom-heading {
			margin-left: 0.75rem;
			font-weight: 500;
			color: #ebf4ff;
		}

		.desktop-heading {
			display: none;
		}

		@media (min-width: 768px) {
		.desktop-heading {
			display: block;
		}
		}

		/* CTA button styling */
		.custom-cta-wrapper {
			order: 3;
			width: 100%;
			margin-top: 0.5rem;
		}

		@media (min-width: 640px) {
		.custom-cta-wrapper {
			order: 2;
			width: auto;
			margin-top: 0;
		}
		}

		.custom-cta-button {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 0.5rem 1rem;
			font-size: 0.875rem;
			font-weight: 500;
			background-color: #ffffff;
			border: 1px solid transparent;
			border-radius: 0.375rem;
			color: #2c5282;
			transition: color 150ms ease-in-out;
			text-decoration: inherit;
		}

		.custom-cta-button:hover {
			color: #434190;
		}

		/* Close button styling */
		.custom-close-wrapper {
			order: 2;
			margin-left: 0.5rem;
		}

		@media (min-width: 640px) {
			.custom-close-wrapper {
				order: 3;
			}
		}

		.custom-close-wrapper:hover {
			background-color: #42389da6 !important;
			border-radius: 0.375rem;
			opacity: 1;
		}

		.custom-close-wrapper:focus {
			outline: none;
			background-color: #2b6cb0;
		}

</style>

<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		let closeCta = document.getElementsByClassName('sc_close')
		if(closeCta.length > 0){
			closeCta[0].onclick = (e) => {
				document.querySelector('.sc_action_bar').style.display = 'none'
			}
		}
	})
</script>

<div class="custom-banner-container">
	<div class="custom-banner-content">
		<div class="custom-banner-inner">
		<div class="custom-banner-header">
			<div class="custom-banner-icon-text">
			<span>
				<svg class="custom-icon" stroke="currentColor" fill="none" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
				</svg>
			</span>
			<div class="custom-heading">
				<div class="desktop-heading"><?php echo wp_kses_post( $description ); ?></div>
			</div>
			</div>
			<div class="custom-cta-wrapper">
			<a href="<?php echo esc_url( $button_url ); ?>" class="custom-cta-button">
				<span class="cta-text"><?php echo esc_html( $button_label ); ?></span>
			</a>
			</div>
			<div class="custom-close-wrapper sc_close">
				<svg class="custom-icon" style="background-color: transparent !important; cursor: pointer;" stroke="currentColor" fill="none" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</div>
		</div>
		</div>
	</div>
</div>
</div>
