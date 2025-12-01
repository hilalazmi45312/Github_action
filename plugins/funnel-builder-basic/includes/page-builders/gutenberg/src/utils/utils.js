/**
 * External dependencies
 */
import { isEmpty, values as __values, isEqual, noop, groupBy, cloneDeep, pickBy } from 'lodash';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SVGBlob1, SVGSquare, SVGCircle, IconSquare, IconIndivisual } from './images';

export const getCurrentScreen = () => {
	var deviceType = useSelect((select) => {
		const {
			__experimentalGetPreviewDeviceType = null,
		} = select('core/edit-post');
		return __experimentalGetPreviewDeviceType ? __experimentalGetPreviewDeviceType() : 'Desktop';
	}, []);
	deviceType = deviceType ? deviceType.toLowerCase() : 'desktop';
	return deviceType;
}
export const includeFont = (font) => {
	if (font) {
		const fonts = [
			font.desktop && font.desktop.family && !systemFonts.includes(font.desktop.family)
				? (
					font.desktop.family +
					':' +
					(font.desktop.weight ? font.desktop.weight : 'regular')
				).replace(/\s/g, '+')
				: '',
			font.tablet && font.tablet.family && !systemFonts.includes(font.desktop.family)
				? (
					font.tablet.family +
					':' +
					(font.tablet.weight ? font.tablet.weight : 'regular')
				).replace(/\s/g, '+')
				: '',
			font.mobile && font.mobile.family && !systemFonts.includes(font.desktop.family)
				? (
					font.mobile.family +
					':' +
					(font.mobile.weight ? font.mobile.weight : 'regular')
				).replace(/\s/g, '+')
				: '',
		].filter(function (el) {
			return el !== '';
		});
		if (fonts.length) {
			WebFont.load({
				google: {
					families: fonts,
				},
			});
			includeFontInIframe(fonts)
		}
	}
};

/**
 *
 * @param {string} cssAttr created for padding and margin computation
 * @param {Object} data    padding, margin data collection
 * @param {string} screen  responsive screen
 * @return
 */
export const cssSpaceCompute = (cssAttr, data, screen = 'desktop') => {
	if (isEmpty(data[screen])) {
		return false;
	}

	if ('desktop' !== screen) {
		if (isEqual(data[screen], data.desktop)) {
			return false;
		}
	}
	const { unit, ...tempVal } = data[screen];
	const tempArr = __values(tempVal);

	if (tempArr.every((item) => item === tempArr[0])) {
		return `${cssAttr}: ${tempArr[0]}${unit};`;
	}

	let spaceCSS = '';
	if (!isEmpty(tempVal.top)) {
		spaceCSS += `${cssAttr}: ${tempVal.top}${unit};`;
	}
	if (!isEmpty(tempVal.right)) {
		spaceCSS += `${cssAttr}: ${tempVal.right}${unit};`;
	}
	if (!isEmpty(tempVal.bottom)) {
		spaceCSS += `${cssAttr}: ${tempVal.bottom}${unit};`;
	}
	if (!isEmpty(tempVal.left)) {
		spaceCSS += `${cssAttr}: ${tempVal.left}${unit};`;
	}
	return spaceCSS;
};

/**
 * Default colors for color pallete
 */
export const defaultColors = [
	{
		name: __('Black', 'funnel-builder-powerpack'),
		slug: 'black',
		color: '#000000',
	},
	{
		name: __('Cyan bluish gray', 'funnel-builder-powerpack'),
		slug: 'cyan-bluish-gray',
		color: '#abb8c3',
	},
	{
		name: __('White', 'funnel-builder-powerpack'),
		slug: 'white',
		color: '#ffffff',
	},
	{
		name: __('Pale pink', 'funnel-builder-powerpack'),
		slug: 'pale-pink',
		color: '#f78da7',
	},
	{
		name: __('Vivid red', 'funnel-builder-powerpack'),
		slug: 'vivid-red',
		color: '#cf2e2e',
	},
	{
		name: __('Luminous vivid orange', 'funnel-builder-powerpack'),
		slug: 'luminous-vivid-orange',
		color: '#ff6900',
	},
	{
		name: __('Luminous vivid amber', 'funnel-builder-powerpack'),
		slug: 'luminous-vivid-amber',
		color: '#fcb900',
	},
	{
		name: __('Light green cyan', 'funnel-builder-powerpack'),
		slug: 'light-green-cyan',
		color: '#7bdcb5',
	},
	{
		name: __('Vivid green cyan', 'funnel-builder-powerpack'),
		slug: 'vivid-green-cyan',
		color: '#00d084',
	},
	{
		name: __('Pale cyan blue', 'funnel-builder-powerpack'),
		slug: 'pale-cyan-blue',
		color: '#8ed1fc',
	},
	{
		name: __('Vivid cyan blue', 'funnel-builder-powerpack'),
		slug: 'vivid-cyan-blue',
		color: '#0693e3',
	},
	{
		name: __('Vivid purple', 'funnel-builder-powerpack'),
		slug: 'vivid-purple',
		color: '#9b51e0',
	},
];
export const textAlignSetting = [
	{
		id: 'left',
		icon: 'editor-alignleft',
		label: __('Left', 'funnel-builder-powerpack'),
	},
	{
		id: 'center',
		icon: 'editor-aligncenter',
		label: __('Center', 'funnel-builder-powerpack'),
	},
	{
		id: 'right',
		icon: 'editor-alignright',
		label: __('Right', 'funnel-builder-powerpack'),
	},
	{
		id: 'justify',
		icon: 'editor-justify',
		label: __('Justify', 'funnel-builder-powerpack'),
	},
];
export const textDecorationSetting = [
	{
		id: 'none',
		icon: 'editor-textcolor',
		label: __('None', 'funnel-builder-powerpack'),
	},
	{
		id: 'line-through',
		icon: 'editor-strikethrough',
		label: __('Line Through', 'funnel-builder-powerpack'),
	},
	{
		id: 'underline',
		icon: 'editor-underline',
		label: __('Underline', 'funnel-builder-powerpack'),
	},
	{
		id: 'overline',
		icon: 'ellipsis',
		label: __('Overline', 'funnel-builder-powerpack'),
	},
];

export const textTransformSetting = [
	{
		id: 'none',
		label: __('None', 'funnel-builder-powerpack'),
	},
	{
		id: 'capitalize',
		label: __('Capitalize', 'funnel-builder-powerpack'),
	},
	{
		id: 'uppercase',
		label: __('Uppercase', 'funnel-builder-powerpack'),
	},
	{
		id: 'lowercase',
		label: __('Lowercase', 'funnel-builder-powerpack'),
	},
];

const fontsarray =
	// eslint-disable-next-line camelcase
	typeof bwfop_funnels_data !== 'undefined' &&
		bwfop_funnels_data.bwf_g_font_names
		? bwfop_funnels_data.bwf_g_font_names.map((name) => {
			return { label: name, value: name, google: true };
		})
		: {};

export const FontFamilyList = [
	{
		type: 'group',
		label: __('Standard Fonts', 'funnel-builder-powerpack'),
		options: [
			{
				label: 'System Default',
				value: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"',
				google: false,
			},
			{
				label: 'Arial, Helvetica, sans-serif',
				value: 'Arial, Helvetica, sans-serif',
				google: false,
			},
			{
				label: '"Arial Black", Gadget, sans-serif',
				value: '"Arial Black", Gadget, sans-serif',
				google: false,
			},
			{
				label: 'Helvetica, sans-serif',
				value: 'Helvetica, sans-serif',
				google: false,
			},
			{
				label: '"Comic Sans MS", cursive, sans-serif',
				value: '"Comic Sans MS", cursive, sans-serif',
				google: false,
			},
			{
				label: 'Impact, Charcoal, sans-serif',
				value: 'Impact, Charcoal, sans-serif',
				google: false,
			},
			{
				label: '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
				value: '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
				google: false,
			},
			{
				label: 'Tahoma, Geneva, sans-serif',
				value: 'Tahoma, Geneva, sans-serif',
				google: false,
			},
			{
				label: '"Trebuchet MS", Helvetica, sans-serif',
				value: '"Trebuchet MS", Helvetica, sans-serif',
				google: false,
			},
			{
				label: 'Verdana, Geneva, sans-serif',
				value: 'Verdana, Geneva, sans-serif',
				google: false,
			},
			{
				label: 'Georgia, serif',
				value: 'Georgia, serif',
				google: false,
			},
			{
				label: '"Palatino Linotype", "Book Antiqua", Palatino, serif',
				value: '"Palatino Linotype", "Book Antiqua", Palatino, serif',
				google: false,
			},
			{
				label: '"Times New Roman", Times, serif',
				value: '"Times New Roman", Times, serif',
				google: false,
			},
			{
				label: 'Courier, monospace',
				value: 'Courier, monospace',
				google: false,
			},
			{
				label: '"Lucida Console", Monaco, monospace',
				value: '"Lucida Console", Monaco, monospace',
				google: false,
			},
		],
	},
	{
		type: 'group',
		label: __('Google Fonts', 'funnel-builder-powerpack'),
		options: fontsarray,
	},
];
const systemFonts = FontFamilyList[0].options.map(opt => opt.value);

export const FontWeightList = [
	{
		value: 'inherit',
		label: __('Inherit', 'funnel-builder-powerpack'),
	},
	{ value: '100', label: __('Thin 100', 'funnel-builder-powerpack') },
	{
		value: '200',
		label: __('Extra-Light 200', 'funnel-builder-powerpack'),
	},
	{ value: '300', label: __('Light 300', 'funnel-builder-powerpack') },
	{ value: '400', label: __('Regular', 'funnel-builder-powerpack') },
	{
		value: '500',
		label: __('Medium 500', 'funnel-builder-powerpack'),
	},
	{
		value: '600',
		label: __('Semi-Bold 600', 'funnel-builder-powerpack'),
	},
	{ value: '700', label: __('Bold 700', 'funnel-builder-powerpack') },
	{
		value: '800',
		label: __('Extra-Bold 800', 'funnel-builder-powerpack'),
	},
	{
		value: '900',
		label: __('Ultra-Bold 900', 'funnel-builder-powerpack'),
	},
];

export const faGetSVGIcon = (prefix, iconName) => {
	const icon = faGetIcon(prefix, iconName);
	if (icon) {
		return icon.html[0];
	}
	return '';
};

export const faGetIcon = (prefix, iconName) => {
	if (!window.FontAwesome) {
		return null;
	}
	return window.FontAwesome.icon({ prefix, iconName });
};

export const faIsAPILoaded = () => {
	return !!window.FontAwesome;
};

export const faAPILoaded = () => {
	if (!window.FontAwesome) {
		return new Promise((resolve, reject) => {
			let timeoutCounter = 240;
			const interval = setInterval(() => {
				if (window.FontAwesome) {
					clearInterval(interval);
					resolve(true);
				} else if (timeoutCounter-- < 0) {
					clearInterval(interval);
					reject(false);
				}
			}, 250);
		});
	}
	return Promise.resolve(true);
};

export const faIconLoaded = (prefix, iconName) => {
	const icon = faGetIcon(prefix, iconName);
	if (!icon) {
		return new Promise((resolve, reject) => {
			let timeoutCounter = 240;
			const interval = setInterval(() => {
				const icon = faGetIcon(prefix, iconName);
				if (window.FontAwesome) {
					clearInterval(interval);
					resolve(icon);
				} else if (timeoutCounter-- < 0) {
					clearInterval(interval);
					reject(false);
				}
			}, 250);
		});
	}
	return Promise.resolve(icon);
};

export const getShapeSVG = (shape) => {
	const SVGS = {
		circle: SVGCircle,
		square: SVGSquare,
		blob1: SVGBlob1,
	};
	return !SVGS[shape] ? null : SVGS[shape];
};

export const DEFAULT_CHECK_SVG =
	'<svg data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path></svg>';
export const DEFAULT_TICK_SVG =
	'<svg data-prefix="far" data-icon="check-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" > <path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z" ></path> </svg>';

export const computeSpaceValue = (prop, data) => {
	const temp = lodash.cloneDeep(prop ? prop : {});
	Object.keys(temp).forEach(function (key, idx) {
		temp[key] = data[idx];
	});
	return temp;
};
const TABS = [
	{
		id: 'normal',
		label: __('Normal', 'funnel-builder-powerpack'),
	},
	{
		id: 'hover',
		label: __('Hover', 'funnel-builder-powerpack'),
	},
];

export const hoverTab = (screenType, setScreen, extraTab = [], customTab) => {
	const tabArr = customTab ? customTab : [...TABS, ...extraTab];
	return (
		<div className={classNames('bwf-responsive-tabs-list bwf-hover-tab')}>
			{tabArr.map((TData) => {
				return (
					<Button
						key={TData.id}
						onClick={() => {
							setScreen(TData.id);
						}}
						className={classNames('bwf-screen-tabs', {
							'active-screen': screenType === TData.id,
						})}
					>
						<Tooltip position="top center" text={TData.label}>
							<span className={'bwf-device-brick'}>
								{TData.label}
							</span>
						</Tooltip>
					</Button>
				);
			})}
		</div>
	);
}

export const rgba2hex = (orig) => {
	let a,
		rgb = orig
			.replace(/\s/g, '')
			.match(/^rgba?\((\d+),(\d+),(\d+),?([^,\s)]+)?/i),
		alpha = ((rgb && rgb[4]) || '').trim(),
		hex = rgb
			? (rgb[1] | (1 << 8)).toString(16).slice(1) +
			(rgb[2] | (1 << 8)).toString(16).slice(1) +
			(rgb[3] | (1 << 8)).toString(16).slice(1)
			: orig;

	if (alpha !== '') {
		a = alpha;
	} else {
		a = 1;
	}
	// multiply before convert to HEX
	a = ((a * 255) | (1 << 8)).toString(16).slice(1);
	hex = hex + a;
	return `#${hex}`;
};

export const DimensionToggle = (props) => {
	const {
		onChange = noop,
		initial = true,
	} = props;
	const [toggle, setToggle] = useState(initial);
	const handleToggle = () => {
		setToggle(!toggle)
		onChange(!toggle)
	}
	return (
		toggle
			? <span className={classNames('bwf-dimension-toggle')} onClick={handleToggle} title={'Edit All'}>
				<IconSquare />
			</span>
			: <span className={classNames('bwf-dimension-toggle')} onClick={handleToggle} title={'Edit Indivisual'}>
				<IconIndivisual />
			</span>
	)
}

export const breakPoints = (props = {}) => {
	const { clientId } = props;
	return {
		'tablet': clientId ? '5000px' : '1024px',
		'mobile': clientId ? '5000px' : '767px',
	}
}

/**
 * Simple CSS minification.
 *
 * @see https://stackoverflow.com/questions/15411263/how-do-i-write-a-better-regexp-for-css-minification-in-php
 *
 * @param {string} css CSS to minify.
 * @param {boolean} important Add !important to all rules.
 *
 * @return {string} Minified CSS
 */
export const minifyCSS = (css, important = false) => {
	const minified = css.replace(/\/\*.*?\*\//g, '') // Comments.
		.replace(/\n\s*\n/g, '') // Comments.
		.replace(/[\n\r \t]/g, ' ') // Spaces.
		.replace(/ +/g, ' ') // Multi-spaces.
		.replace(/ ?([,:;{}]) ?/g, '$1') // Extra spaces.
		.replace(/[^\}\{]+\{\}/g, '') // Blank selectors.
		.replace(/[^\}\{]+\{\}/g, '') // Blank selectors. Repeat to catch empty media queries.
		.replace(/;}/g, '}') // Trailing semi-colon.
		.trim()

	if (!important) {
		return minified
	}

	return minified
		.replace(/\s?\!important/g, '') // Remove all !important
		.replace(/([;\}])/g, ' !important$1') // Add our own !important.
		.replace(/\} !important\}/g, '}}') // Ending of media queries "}}" get an added !important from the previous line, remove it.
		.trim()
}

export const hasBlockVisible = (selector = '', props) => {
	if (!selector) {
		return '';
	}

	const { attributes: { vsdesk, vsmobile, vstablet } } = props;
	const screen = getCurrentScreen();
	let visibilityStyle = '';
	switch (screen) {
		case 'desktop':
			if (true === vsdesk) {
				visibilityStyle += `${selector}{opacity:0.1};`;
			}
			break;
		case 'tablet':
			if (true === vstablet) {
				visibilityStyle += `${selector}{opacity:0.1};`;
			}
			break;
		case 'mobile':
			if (true === vsmobile) {
				visibilityStyle += `${selector}{opacity:0.1};`;
			}
			break;
	}
	if (visibilityStyle) {
		return (
			<style>{visibilityStyle}</style>
		)
	}
	return '';
}

export const objectGrouped = (obj, groupBy) => {
	let StyleObj = {};
	for (let x in obj) {
		if (!StyleObj.hasOwnProperty(obj[x][groupBy])) {
			let newObj = pickBy(obj, { [groupBy]: obj[x][groupBy] });
			let data = cloneDeep(StyleObj);
			data[obj[x][groupBy]] = newObj;
			StyleObj = data;
		}
	}
	return StyleObj;
}

export const PanelTitle = [
	{
		'key': 'width',
		'label': __('Width', 'funnel-builder-powerpack'),
	},
	{
		'key': 'Height',
		'label': __('Height', 'funnel-builder-powerpack'),
	},
	{
		'key': 'padding',
		'label': __('Padding', 'funnel-builder-powerpack'),
	},
	{
		'key': 'margin',
		'label': __('Margin', 'funnel-builder-powerpack'),
	},
	{
		'key': 'border',
		'label': __('Border', 'funnel-builder-powerpack'),
	},
	{
		'key': 'text',
		'label': __('Text', 'funnel-builder-powerpack'),
	},
	{
		'key': 'color',
		'label': __('Button Color', 'funnel-builder-powerpack'),
	},
	{
		'key': 'colorHover',
		'label': __('Color Hover', 'funnel-builder-powerpack'),
	},
	{
		'key': 'seccolor',
		'label': __('Button Secondary Color', 'funnel-builder-powerpack'),
	},
	{
		'key': 'seccolorHover',
		'label': __('Secondary Color Hover', 'funnel-builder-powerpack'),
	},
	{
		'key': 'offercolor',
		'label': __('Offer Price Color', 'funnel-builder-powerpack'),
	},
	{
		'key': 'offercolorHover',
		'label': __('Offer Price Color Hover', 'funnel-builder-powerpack'),
	},
	{
		'key': 'backgroundcolor',
		'label': __('Background Color', 'funnel-builder-powerpack'),
	},
	{
		'key': 'buttonbackground',
		'label': __('Button Background', 'funnel-builder-powerpack'),
	},
	{
		'key': 'zIndex',
		'label': __('Z Index', 'funnel-builder-powerpack'),
	},
	{
		'key': 'font',
		'label': __('Font', 'funnel-builder-powerpack'),
	},
	{
		'key': 'boxShadow',
		'label': __('Box Shadow', 'funnel-builder-powerpack'),
	},
	{
		'key': 'structure',
		'label': __('Structure', 'funnel-builder-powerpack'),
	},
	{
		'key': 'space',
		'label': __('Spacing', 'funnel-builder-powerpack'),
	},
	{
		'key': 'typography',
		'label': __('Button Typography', 'funnel-builder-powerpack'),
	},
	{
		'key': 'sectypography',
		'label': __('Button Secondary Typography', 'funnel-builder-powerpack'),
	},
	{
		'key': 'label',
		'label': __('Label', 'funnel-builder-powerpack'),
	},
	{
		'key': 'asteriskcolor',
		'label': __('Asterisk', 'funnel-builder-powerpack'),
	},
	{
		'key': 'input',
		'label': __('Input', 'funnel-builder-powerpack'),
	},
	{
		'key': 'submitbutton',
		'label': __('Submit Button', 'funnel-builder-powerpack'),
	},
	{
		'key': 'popupbtn',
		'label': __('Call to Action Button', 'funnel-builder-powerpack'),
	},
	{
		'key': 'popupheading',
		'label': __('Popup Heading', 'funnel-builder-powerpack'),
	},
	{
		'key': 'popupsubheading',
		'label': __('Popup Sub Heading', 'funnel-builder-powerpack'),
	},
	{
		'key': 'popupheadingafter',
		'label': __('Popup Text After', 'funnel-builder-powerpack'),
	},
	{
		'key': 'headingtypography',
		'label': __('Heading Typography', 'funnel-builder-powerpack'),
	},
	{
		'key': 'headingcolor',
		'label': __('Heading Color', 'funnel-builder-powerpack'),
	},
	{
		'key': 'subheadingtypography',
		'label': __('Sub Heading Typography', 'funnel-builder-powerpack'),
	},
	{
		'key': 'subheading',
		'label': __('Sub Heading', 'funnel-builder-powerpack'),
	},
	{
		'key': 'heading',
		'label': __('Heading', 'funnel-builder-powerpack'),
	},
	{
		'key': 'subheadingcolor',
		'label': __('Popup Sub Heading Color', 'funnel-builder-powerpack'),
	},
	{
		'key': 'background',
		'label': __('Background', 'funnel-builder-powerpack'),
	},
	{
		'key': 'progressbar',
		'label': __('Popup Progress Bar', 'funnel-builder-powerpack'),
	},
	{
		'key': 'popupcross',
		'label': __('Popup Close Button', 'funnel-builder-powerpack'),
	},
	{
		'key': 'thumbnails',
		'label': __('Thumbnails', 'funnel-builder-powerpack'),
	},
	{
		'key': 'featureimage',
		'label': __('Featured Image', 'funnel-builder-powerpack'),
	},

];


export const AttributesType = {
	color: {
		type: 'color',
		label: __('Button Color', 'funnel-builder-powerpack'),
		group: 'color',
	},
	colorHover: {
		type: 'color',
		label: __('Button Hover', 'funnel-builder-powerpack'),
		group: 'color',
	},
	text: {
		type: 'text',
		label: __('', 'funnel-builder-powerpack'),
		group: 'typography',
	},
	lineHeight: {
		type: 'rangeUnit',
		label: __('', 'funnel-builder-powerpack'),
		group: 'typography',
	},
	letterSpacing: {
		type: 'rangeUnit',
		label: __('', 'funnel-builder-powerpack'),
		group: 'typography',
	},
	font: {
		type: 'font',
		label: __('', 'funnel-builder-powerpack'),
		group: 'typography',
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
	padding: {
		type: 'padding',
		label: __('Padding', 'funnel-builder-powerpack'),
		group: 'space',
	},
	margin: {
		type: 'margin',
		label: __('Margin', 'funnel-builder-powerpack'),
		group: 'space',
	},
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

/**
 * function to return string with capital letter.
 * @param {string} string the word string.
 * @returns {string} with capital letter.
 */
export const capitalizeFirstLetter = (string) => {
	return string.charAt(0).toUpperCase() + string.slice(1);
}


wp.domReady(function () {
	document.addEventListener('click', (e) => {
		if ('SELECT' === e.target.tagName && e.target.querySelector('[value="wfop-canvas.php"]')) {
			if ('wfop-canvas.php' === e.target.value) {
				document.body.classList.add('bwf-editor-width-canvas');
				document.body.classList.remove('bwf-editor-width-boxed');

			} else if ('wfop-boxed.php' === e.target.value) {
				document.body.classList.add('bwf-editor-width-boxed');
				document.body.classList.remove('bwf-editor-width-canvas');
			} else {
				document.body.classList.remove('bwf-editor-width-canvas');
				document.body.classList.remove('bwf-editor-width-boxed');
			}
		}
	});
});

export const fontCheck = (props) => {
	let fontArray = [];
	fontArray = pickBy(props, function (value, key) {

		return key.match(/font/gi);
	});
	if (fontArray && Object.keys(fontArray).length) {
		Object.keys(fontArray).forEach(font => includeFont(fontArray[font]));
	}
}
export const toolbarBGIndicator = (background, screen = 'desktop') => {
	let BGStyleIndicator = {};
	let calcStyle = [];
	if (background && background[screen]) {
		if (background[screen].color) {
			calcStyle.push(background[screen].color);
		}
		if (background[screen].gradient) {
			calcStyle.push(background[screen].gradient);
		}
		if (calcStyle.length > 0) {
			BGStyleIndicator = { background: calcStyle.join(' ') }
		}
	}
	return BGStyleIndicator;

}

export const WP_VERSION = window.hasOwnProperty( 'bwfop_funnels_data' ) ? bwfop_funnels_data.wp_version : '';

export const includeFontInIframe = (fonts, prevFont = {}) => {
    if(fonts && select('core/edit-post').__experimentalGetPreviewDeviceType() !== 'Desktop'){
        setTimeout(() => {
            const editorFrame = document.querySelector( 'iframe[name="editor-canvas"]' );
            if(editorFrame){
                const head = editorFrame.contentDocument.head;
                fonts.forEach((value) => {
                    if(!isEmpty(value)){
                        let link = document.createElement('link')
                        link.rel = 'stylesheet';
                        link.href = `https://fonts.googleapis.com/css?family=${value}`
                        head.appendChild(link)

                        if(fonts.length === 1){
                            if(prevFont.current){
                                let prevLink = editorFrame.contentDocument.querySelector(`link[href*="family=${prevFont.current}"]`);
                                if(prevLink){
                                    prevLink.remove();
                                }
                            }
                            prevFont.current = value
                        }
                    }
                })
            }
        }, 500)
    }
}


export const createUniqueClass = uid => `${ uid.substring( 0, 7 ) }`

export const useUniqueId = ( props, isfontCheck=true ) => {
	const { attributes:{uniqueID}, setAttributes, clientId, name: blockName } = props;

	// Need to do this when the clientId changes (when a block is
	// cloned/duplicated).
	useEffect( () => {
		// When there's no unique ID yet, create one.
		const uniqueClass = createUniqueClass( clientId );

        if ( ! uniqueID ) {
            setAttributes({uniqueID: uniqueClass});
			// If there's one already, check whether the we need to re-create one.
			// Duplicating a block or copy pasting a block may give us duplicate IDs.
		} else if ( uniqueClass !== uniqueID ) {
			if ( document.querySelectorAll( `[data-bwfblock-id="${ uniqueID }"]` ).length > 1 ) {
                props.attributes.uniqueID = uniqueClass;
			}
		}

        if( isfontCheck ) {
            fontCheck(props.attributes); //check and load font initially
        }
	}, [ clientId ] );
}