<?php
/**
 * Smart Coupons store notice design - Balloon
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
<div class="sc_action_bar sc_container sc_hello sc_no_hide sc_bottom sc_no_icon sc_show sc_anim_no-anim_in"
	id="sc_action_bar_style_1" style="color: rgb(255, 255, 255); background-color: rgb(33, 35, 50); display: block;">

	<style type="text/css">
	#sc_action_bar_style_1 {
		border-bottom: none;
		border-top: none;
		font-size: 1.2em;
		font-weight: 600;
		font-family: Optima, Segoe, Segoe UI, Candara, Calibri, Arial, sans-serif;
		letter-spacing: 2px;
	}

	#sc_action_bar_style_1 .sc_data {
		padding: 0;
	}

	#sc_action_bar_style_1 .avatar {
		float: left;
		display: inline;
		padding-right: 2rem;
		transform: rotate(334.676deg);
	}

	#sc_action_bar_style_1 .sc_message {
		padding: 0.5rem 0;
	}

	#sc_action_bar_style_1 .highlights {
		font-size: 37px;
		letter-spacing: 3px;
		margin: 0px -10px 0 -10px;
	}

	#sc_action_bar_style_1 .sc_heading {
		margin-top: 1rem;
		letter-spacing: 0.02rem;
		display: inline-block;
		font-weight: 700;
		font-size: 1.5rem;
	}

	#sc_action_bar_style_1 .sc_button {
		font-weight: 600;
		border-radius: 20px;
		padding: 0.5rem 1rem;
		margin-left: 30px;
	}

	@media only screen and (min-width: 300px) and (max-width: 1023px) {
		#sc_action_bar_style_1 .sc_data {
			padding: 0;
		}

		#sc_action_bar_style_1 .avatar {
			display: none;
		}

		#sc_action_bar_style_1 .sc_button {
			margin: 0;
		}

		#sc_action_bar_style_1 .sc_offer_text {
			font-size: 1.5em;
			border-right: 0;
			margin-right: 0;
			padding-right: 0
		}

		#sc_action_bar_style_1 .sc_content {
			margin: 1rem 0;
		}

		#sc_action_bar_style_1 .sc_heading {
			font-size: 1.5em;
			margin-top: 1.2rem;
		}

	}

	/* Action Bar CSS */
	.sc_action_bar.sc_bold .sc_data,
	.sc_action_bar.sc_form_bottom.sc_air-mail .sc_form_container.layout_bottom .sc_embed_form_container,
	.sc_action_bar.sc_form_bottom.sc_air-mail .sc_form_container.layout_bottom .sc_form_footer,
	.sc_action_bar.sc_form_bottom.sc_air-mail .sc_form_container.layout_bottom .sc_form_header,
	.sc_action_bar.sc_form_bottom.sc_bold .sc_form_container.layout_bottom .sc_embed_form_container,
	.sc_action_bar.sc_form_bottom.sc_bold .sc_form_container.layout_bottom .sc_form_footer,
	.sc_action_bar.sc_form_bottom.sc_bold .sc_form_container.layout_bottom .sc_form_header,
	.sc_action_bar.sc_form_inline.sc_air-mail .sc_form_container.layout_inline .sc_embed_form_container,
	.sc_action_bar.sc_form_inline.sc_air-mail .sc_form_container.layout_inline .sc_form_footer,
	.sc_action_bar.sc_form_inline.sc_air-mail .sc_form_container.layout_inline .sc_form_header,
	.sc_action_bar.sc_form_inline.sc_bold .sc_form_container.layout_inline .sc_embed_form_container,
	.sc_action_bar.sc_form_inline.sc_bold .sc_form_container.layout_inline .sc_form_footer,
	.sc_action_bar.sc_form_inline.sc_bold .sc_form_container.layout_inline .sc_form_header {
		text-align: left
	}

	.sc_action_bar,
	.sc_action_bar div {
		-webkit-box-sizing: border-box;
		box-sizing: border-box
	}

	.sc_action_bar.sc_container {
		position: fixed;
		width: 100%;
		padding: 0;
		margin: 0;
		display: none;
		left: 0;
		line-height: 1.5;
		z-index: 9999999
	}

	.sc_action_bar.sc_container.sc_top {
		top: 0
	}

	.sc_action_bar.sc_container.sc_bottom {
		position: fixed;
		bottom: 0
	}

	.sc_action_bar.sc_hide.sc_bottom {
		--transform-translate-x: 0;
		--transform-rotate: 0;
		--transform-skew-x: 0;
		--transform-skew-y: 0;
		--transform-scale-x: 1;
		--transform-scale-y: 1;
		-webkit-transform: translateX(var(--transform-translate-x)) translateY(var(--transform-translate-y)) rotate(var(--transform-rotate)) skewX(var(--transform-skew-x)) skewY(var(--transform-skew-y)) scaleX(var(--transform-scale-x)) scaleY(var(--transform-scale-y));
		transform: translateX(var(--transform-translate-x)) translateY(var(--transform-translate-y)) rotate(var(--transform-rotate)) skewX(var(--transform-skew-x)) skewY(var(--transform-skew-y)) scaleX(var(--transform-scale-x)) scaleY(var(--transform-scale-y));
		--transform-translate-y: 100%;
		-ms-transform: translateY(100%);
		-webkit-transform: translateY(100%)
	}

	.sc_action_bar.sc_hide.sc_top {
		-webkit-transform: translateY(-100%);
		transform: translateY(-100%)
	}

	.sc_action_bar .sc_content {
		float: left;
		text-align: center;
		width: 100%;
		padding: 0 2.5em 0 0
	}

	.sc_action_bar .sc_data {
		text-align: center;
		display: inline-block;
		vertical-align: middle;
		margin: 0;
		line-height: 1.5;
		padding: .3em 1em .3em .7em
	}

	.sc_action_bar.sc_has_pwby .sc_data {
		padding-left: 2.5em
	}

	.sc_action_bar .sc_headline {
		display: inline-block;
		font-weight: 700;
		padding: 0;
		line-height: 1.25;
		font-size: 1em
	}

	.sc_action_bar .sc_message {
		display: inline-block;
		padding: .2em 0 0;
		font-size: .85em;
		line-height: 1.2
	}

	.sc_action_bar .sc_button,
	.sc_action_bar input[type=submit],
	.sc_action_bar input[type=button] {
		border-style: none;
		cursor: pointer;
		display: inline-block;
		font-weight: 700;
		vertical-align: middle;
		text-align: center;
		float: none;
		--text-opacity: 1;
		color: #fff;
		color: rgba(255, 255, 255, var(--text-opacity));
		background-image: none;
		font-size: 1em;
		letter-spacing: .05em;
		padding: .3em 1.5em;
		margin: .5em 0
	}

	.sc_action_bar .sc_close {
		cursor: pointer;
		position: absolute;
		right: 0;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		width: 36px;
		height: 36px;
		z-index: 1000000
	}

	.sc_action_bar .sc_close>span {
		height: 100%;
		display: inline-block;
		background-image: url(<?php echo esc_url( untrailingslashit( plugins_url( '/', WC_SC_PLUGIN_FILE ) ) . '/assets/images/sprite.png' ); ?>);
		background-repeat: no-repeat;
		width: 30px;
		opacity: .7
	}

	.sc_action_bar .sc_close:hover>span {
		opacity: 1
	}

	.sc_action_bar.sc_show .sc_close {
		background-color: transparent !important
	}

	.sc_action_bar.sc_hide .sc_close {
		position: fixed;
		border-style: none;
		right: .2em
	}

	.sc_action_bar.sc_hide.sc_top .sc_close {
		top: 100%
	}

	.sc_action_bar.sc_hide.sc_bottom .sc_close {
		bottom: 100%
	}

	.sc_action_bar.sc_hide.sc_bottom .sc_close>span,
	.sc_action_bar.sc_show.sc_top .sc_close>span {
		background-position: -80px center
	}

	.sc_action_bar.sc_hide.sc_top .sc_close>span,
	.sc_action_bar.sc_show.sc_bottom .sc_close>span {
		background-position: -120px center
	}

	@media only screen and (max-width: 768px) {
		.sc_action_bar .sc_content {
			padding-right: 0
		}

		.sc_action_bar .sc_data {
			width: 100%;
			max-width: 100% !important;
			padding-right: 2.5em
		}

		.sc_action_bar .sc_button,
		.sc_action_bar input[type=submit],
		.sc_action_bar input[type=button] {
			max-width: 100%;
			margin: .3em 0
		}

		.sc_action_bar.sc_show .sc_close {
			top: 0
		}

		.sc_action_bar {
			font-size: 18px
		}
	}

	.sc_action_bar.sc_form_bottom.sc_solid .sc_form_container.layout_bottom .sc_embed_form_container,
	.sc_action_bar.sc_form_bottom.sc_solid .sc_form_container.layout_bottom .sc_form_footer,
	.sc_action_bar.sc_form_bottom.sc_solid .sc_form_container.layout_bottom .sc_form_header,
	.sc_action_bar.sc_form_inline.sc_solid .sc_form_container.layout_inline .sc_embed_form_container,
	.sc_action_bar.sc_form_inline.sc_solid .sc_form_container.layout_inline .sc_form_footer,
	.sc_action_bar.sc_form_inline.sc_solid .sc_form_container.layout_inline .sc_form_header {
		text-align: left
	}

	/*  */

	.sc_message p {
		margin: 0;
		padding: 0;
		line-height: inherit;
		font-size: inherit
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

	<?php
	$title_2       = strtoupper( $title_2 );
	$length        = strlen( $title_2 );
	$chunk_size    = $length / 5;
	$total_length  = 0;
	$title_2_array = array();
	for ( $i = 1; $i <= 5; $i++ ) {
		$total_length      += $chunk_size;
		$current_chunk_size = round( $total_length ) / $i;
		$current_chunk_size = round( $current_chunk_size );
		$title_2_array[]    = substr( $title_2, 0, $current_chunk_size );
		$title_2            = substr( $title_2, $current_chunk_size );
	}
	?>

	<div class="sc_content sc_clear_fix">
		<div class="sc_close" style="background-color: rgb(33, 35, 50);"><span></span></div>
		<div class="sc_data sc_clear_fix">
			<div class="sc_headline" style="display: none;"></div>
			<div class="sc_message">
				<div class="avatar"><img src="<?php echo esc_url( untrailingslashit( plugins_url( '/', WC_SC_PLUGIN_FILE ) ) . '/assets/images/balloons.png' ); ?>" width="80"></div>
				<p><span><?php echo esc_html( $title_1 ); ?></span> </p>
				<p class="highlights" style=""> <span><span style="color: rgb(160, 114, 53)"><?php echo esc_html( $title_2_array[0] ); ?></span></span><span style="color:rgb(188, 147, 74)"><?php echo esc_html( $title_2_array[1] ); ?></span><span style="color: rgb(220, 184, 98)"><?php echo esc_html( $title_2_array[2] ); ?></span><span style="color: rgb(188, 147, 74);"><?php echo esc_html( $title_2_array[3] ); ?></span><span style="color: rgb(160, 114, 53);"><?php echo esc_html( $title_2_array[4] ); ?></span> </p>
				<p><span><?php echo wp_kses_post( $description ); ?></span></p>
			</div>
		</div>
		<div class="sc_button sc_button_anim_no-effect"
			style="background-color: rgb(242, 173, 23); border-color: rgb(202, 143, 18); color: rgb(255, 255, 255);">
			<a href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_label ); ?></a></div>
	</div>
</div>
