/**
 * Internal dependencies
 */
import FontAwesomeIcon from '../font-awesome-icon';
import { getShapeSVG } from '../../utils/utils';
import classnames from 'classnames';

const wrapBackgroundShape = (icon, shape) => {
	const ShapeComp = getShapeSVG(shape || 'blob1');
	if (!ShapeComp) {
		return icon;
	}

	return (
		<div className="bwf-icon__bg-shape-wrapper">
			{icon}
			<ShapeComp className="bwf-icon__bg-shape" />
		</div>
	);
};

/**
 * Extracts the first SVG tag it could find in an HTML string.
 *
 * @param {string} htmlString String to extract the svg.
 */
const extractSvg = (htmlString) => {
	if (htmlString.match(/^<svg(.*?)<\/svg>$/g)) {
		return htmlString;
	} else if (htmlString.match(/<svg/)) {
		return (htmlString.match(/<svg.*?<\/svg>/g) || [htmlString])[0];
	}
	return htmlString;
};

const SvgIcon = (props) => {
	const propsToPass = {
		...props,
		value:
			typeof props.value === 'string'
				? extractSvg(props.value)
				: props.value,
	};

	const classNames = classnames(['bwf-icon-inner-svg', props.className], {
		[`bwf-icon--${props.colorType}`]:
			props.colorType && props.colorType !== 'single',
	});

	let ret = <FontAwesomeIcon {...propsToPass} className={classNames} />;

	if (props.design === 'shaped' || props.design === 'outlined') {
		const wrapperClasses = classnames(
			['bwf-icon__design-wrapper', `bwf-icon__design-${props.design}`],
			{
				[`bwf--shadow-${props.shadow}`]:
					props.shadow && props.design === 'shaped',
			}
		);
		ret = <div className={wrapperClasses}>{ret}</div>;
	}

	if (props.showBackgroundShape) {
		ret = wrapBackgroundShape(ret, props.backgroundShape);
	}

	return ret;
};

SvgIcon.defaultProps = {
	className: '',

	value: '', // The icon name or icon SVG.
	design: '', // Can be plain, shaped or outlined

	colorType: '', // Blank/single, gradient or multicolor.

	// Show background shape.
	showBackgroundShape: false,
	backgroundShape: '', // An SVG to add as a background
	shadow: '', // For shaped only

	// The icon has a gradient color.
	gradientColor1: '',
	gradientColor2: '',
	gradientDirection: 0, // Only supports every 45 degrees.
};

SvgIcon.Content = (props) => {
	const propsToPass = {
		...props,
		value:
			typeof props.value === 'string'
				? extractSvg(props.value)
				: props.value,
	};

	const classNames = classnames(['bwf-icon-inner-svg', props.className], {
		[`bwf-icon--${props.colorType}`]:
			props.colorType && props.colorType !== 'single',
	});

	let ret = (
		<FontAwesomeIcon.Content {...propsToPass} className={classNames} />
	);

	if (props.design === 'shaped' || props.design === 'outlined') {
		const wrapperClasses = classnames(
			['bwf-icon__design-wrapper', `bwf-icon__design-${props.design}`],
			{
				[`bwf--shadow-${props.shadow}`]:
					props.shadow && props.design === 'shaped',
			}
		);
		ret = <div className={wrapperClasses}>{ret}</div>;
	}

	if (props.showBackgroundShape) {
		ret = wrapBackgroundShape(ret, props.backgroundShape);
	}

	return ret;
};

SvgIcon.Content.defaultProps = {
	...SvgIcon.defaultProps,
};

export default SvgIcon;
