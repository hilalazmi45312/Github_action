/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const BlockAttributesType = {

	/**
	 * Button
	 */
	alignment: {
		type: 'alignment',
		group: 'popupbtn',
		label: __('Alignment', 'funnel-builder-powerpack'),
	},
	border: {
		type: 'border',
		group: 'popupbtn',
		label: __('Border', 'funnel-builder-powerpack'),
	},
	color: {
		type: 'colorbtn',
		label: __('Text', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	colorHover: {
		type: 'colorbtn',
		label: __('Text Hover', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	text: {
		type: 'typographybtn',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	textHover: {
		type: 'typographybtn',
		label: __('Hover', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	lineHeight: {
		type: 'typographybtn',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	lineHeightHover: {
		type: 'typographybtn',
		label: __('Hover', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	letterSpacing: {
		type: 'typographybtn',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	letterSpacingHover: {
		type: 'typographybtn',
		label: __('Hover', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	font: {
		type: 'typographybtn',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	fontHover: {
		type: 'typographybtn',
		label: __('Hover', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	paddingButton: {
		type: 'paddingbtn',
		label: __('Padding', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	marginButton: {
		type: 'marginbtn',
		label: __('Margin', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	buttonBoxShadow: {
		type: 'boxShadow',
		group: 'popupbtn',
		label: __('Box Shadow', 'funnel-builder-powerpack'),
	},
	buttonBoxShadowHover: {
		type: 'boxShadow',
		group: 'popupbtn',
		label: __('Box Shadow Hover', 'funnel-builder-powerpack'),
	},

	buttonBackground: {
		type: 'background',
		group: 'popupbtn',
		label: __('Button Background', 'funnel-builder-powerpack'),
	},
	buttonBackgroundHover: {
		type: 'background',
		group: 'popupbtn',
		label: __('Background Hover', 'funnel-builder-powerpack'),
	},

	secondaryColor: {
		type: 'colorsec',
		group: 'popupbtn',
		label: __('Subtitle Text', 'funnel-builder-powerpack'),
	},
	secondaryColorHover: {
		type: 'colorsec',
		group: 'popupbtn',
		label: __('Subtitle Text Hover', 'funnel-builder-powerpack'),
	},
	secondaryFont: {
		type: 'typographysec',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	secondaryFontHover: {
		type: 'typographysec',
		label: __(' Hover', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	secondaryText: {
		type: 'typographysec',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	secondaryTextHover: {
		type: 'typographysec',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupbtn',
	},
	secondaryLineHeight: {
		type: 'typographysec',
		group: 'popupbtn',
		label: __('', 'funnel-builder-powerpack'),
	},
	secondaryLineHeightHover: {
		type: 'typographysec',
		group: 'popupbtn',
		label: __('', 'funnel-builder-powerpack'),
	},
	secondaryLetterSpacing: {
		type: 'typographysec',
		group: 'popupbtn',
		label: __('', 'funnel-builder-powerpack'),
	},
	secondaryLetterSpacingHover: {
		type: 'typographysec',
		group: 'popupbtn',
		label: __('', 'funnel-builder-powerpack'),
	},

	buttonWidth: {
		type: 'button',
		group: 'popupbtn',
		label: __('Submit Button', 'funnel-builder-powerpack'),
	},


	/**
	 * Progress Bar
	 */
	progressBackground: {
		type: 'fill-bg',
		label: __('Fill color', 'funnel-builder-powerpack'),
		group: 'progressbar',
	},
	progressBackgroundRemain: {
		type: 'background',
		label: __('', 'funnel-builder-powerpack'),
		group: 'progressbar',
	},
	progressColor: {
		type: 'color',
		group: 'progressbar',
		label: __('Text Color', 'funnel-builder-powerpack'),
	},
	progressBarPadding: {
		type: 'padding',
		group: 'progressbar',
		label: __('Padding', 'funnel-builder-powerpack'),
	},
	progressFont: {
		type: 'font',
		label: __('', 'funnel-builder-powerpack'),
		group: 'progressbar',
	},
	progressText: {
		type: 'text',
		label: __('', 'funnel-builder-powerpack'),
		group: 'progressbar',
	},
	progressLineHeight: {
		type: 'rangeUnit',
		group: 'progressbar',
		label: __('', 'funnel-builder-powerpack'),
	},
	progressLetterSpacing: {
		type: 'rangeUnit',
		group: 'progressbar',
		label: __('', 'funnel-builder-powerpack'),
	},
	/**
	 * Heading
	 */
	headingColor: {
		type: 'headingcolor',
		group: 'popupheading',
		label: __('Text Color', 'funnel-builder-powerpack'),
	},
	headingFont: {
		type: 'headingtypography',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupheading',
	},
	headingText: {
		type: 'headingtypography',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupheading',
	},
	headingLineHeight: {
		type: 'headingtypography',
		group: 'popupheading',
		label: __('', 'funnel-builder-powerpack'),
	},
	headingLetterSpacing: {
		type: 'headingtypography',
		group: 'popupheading',
		label: __('', 'funnel-builder-powerpack'),
	},

	subHeadingColor: {
		type: 'subheadingcolor',
		group: 'popupsubheading',
		label: __('Text Color', 'funnel-builder-powerpack'),
	},
	subHeadingFont: {
		type: 'subheadingtypography',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupsubheading',
	},
	subHeadingText: {
		type: 'subheadingtypography',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupsubheading',
	},
	subHeadingLineHeight: {
		type: 'subheadingtypography',
		group: 'popupsubheading',
		label: __('', 'funnel-builder-powerpack'),
	},
	subHeadingLetterSpacing: {
		type: 'subheadingtypography',
		group: 'popupsubheading',
		label: __('', 'funnel-builder-powerpack'),
	},

	textAfterColor: {
		type: 'textaftercolor',
		group: 'popupheadingafter',
		label: __('Text Color', 'funnel-builder-powerpack'),
	},
	textAfterFont: {
		type: 'textaftertypography',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupheadingafter',
	},
	textAfterText: {
		type: 'textaftertypography',
		label: __('', 'funnel-builder-powerpack'),
		group: 'popupheadingafter',
	},
	textAfterLineHeight: {
		type: 'textaftertypography',
		group: 'popupheadingafter',
		label: __('', 'funnel-builder-powerpack'),
	},
	textAfterLetterSpacing: {
		type: 'textaftertypography',
		group: 'popupheadingafter',
		label: __('', 'funnel-builder-powerpack'),
	},

	crossBorder: {
		type: 'border',
		group: 'popupcross',
		label: __('', 'funnel-builder-powerpack'),
	},
	crossColor: {
		type: 'color',
		group: 'popupcross',
		label: __('Color', 'funnel-builder-powerpack'),
	},
	crossColorHover: {
		type: 'color',
		group: 'popupcross',
		label: __('Color Hover', 'funnel-builder-powerpack'),
	},
	crossBackgroundColor: {
		type: 'background',
		group: 'popupcross',
		label: __('Background Color', 'funnel-builder-powerpack'),
	},
	crossBackgroundColorHover: {
		type: 'background',
		group: 'popupcross',
		label: __('Background Color Hover', 'funnel-builder-powerpack'),
	},

	padding: {
		type: 'padding',
		label: __('Padding', 'funnel-builder-powerpack'),
		group: 'space',
	},
	// margin: {
	// 	type: 'margin',
	// 	label: __('Margin', 'funnel-builder-powerpack'),
	// 	group: 'space',
	// },
	width: {
		type: 'width',
		label: __('Width', 'funnel-builder-powerpack'),
		group: 'structure',
	},
	minWidth: {
		type: 'width',
		label: __('Min Width', 'funnel-builder-powerpack'),
		group: 'structure',
	},
	maxWidth: {
		type: 'width',
		label: __('Max Width', 'funnel-builder-powerpack'),
		group: 'structure',
	},
	height: {
		type: 'height',
		label: __('Height', 'funnel-builder-powerpack'),
		group: 'structure',
	},
	minHeight: {
		type: 'height',
		label: __('Min Height', 'funnel-builder-powerpack'),
		group: 'structure',
	},
	maxHeight: {
		type: 'height',
		label: __('Max Height', 'funnel-builder-powerpack'),
		group: 'structure',
	},
	background: {
		type: 'background',
		label: __('Background', 'funnel-builder-powerpack'),
		group: 'background',
	},
	backgroundHover: {
		type: 'background',
		label: __('Background Hover', 'funnel-builder-powerpack'),
		group: 'background',
	},
	boxShadow: {
		type: 'boxShadow',
		label: __('Box Shadow', 'funnel-builder-powerpack'),
		group: 'boxShadow',
	},
	boxShadowHover: {
		type: 'boxShadow',
		label: __('Box Shadow Hover', 'funnel-builder-powerpack'),
		group: 'boxShadow',
	},
};

export default BlockAttributesType