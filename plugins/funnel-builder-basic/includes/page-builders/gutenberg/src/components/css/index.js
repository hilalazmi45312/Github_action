/**
 * External dependencies
 */
import { isEmpty } from 'lodash';

export const getBoxShadowSettingCss = (boxShadow) => {
	const { h_offset, v_offset, blur, spread, color, inset } = boxShadow;
	if ( (h_offset === '' || h_offset == undefined) && (v_offset === '' || v_offset == undefined) && (blur === '' || blur == undefined) && (spread === '' || spread == undefined) && (color === '' || color == undefined) ) {
		return '';	
	}
	return (
		(h_offset ? h_offset : 0 ) +
		'px ' +
		(v_offset ? v_offset : 0 ) +
		'px ' +
		(blur ? blur : 0 ) +
		'px ' +
		(spread ? spread : 0 ) +
		'px ' +
		(color ? color : 'transparent' ) +
		' ' +
		(inset ? 'inset' : '')
	);
};

export const computeUnitValueStyle = ( data, style='width', defaultUnit = 'px', defaultKey = 'value', defaultUnitKey='unit' ) => {
	let css = '';
	if( data && data.hasOwnProperty([defaultKey]) ) {
		if( '' !== data[defaultKey] ) {
			let unit = data && data[defaultUnitKey] ? data[defaultUnitKey] : defaultUnit;
			css = `${style}:${data[defaultKey]}${unit};`
		}
	}
	return css;
}

export const widthCss = (width, style = 'width') => {
	if (width && width.width) {
		return `${
			width && width.width
				? `${style}:${width.width}${width.unit ? width.unit : 'px'};`
				: ``
		}`;
	}
	return `${
		width && width.desktop && width.desktop.width
			? `${style}:${width.desktop.width}${
					width.desktop.unit ? width.desktop.unit : 'px'
			};`
			: ``
	}`;
};

export const heightCss = (height, style = 'height') => {
	if (height && height.height) {
		return `${
			height && height.height
				? `${style}:${height.height}${
						height.unit ? height.unit : 'px'
				};`
				: ``
		}`;
	}
	return `${
		height && height.desktop && height.desktop.height
			? `${style}:${height.desktop.height}${
					height.desktop.unit ? height.desktop.unit : 'px'
			};`
			: ``
	}`;
};

export function shorthandCSS( top, right, bottom, left, unit ) {
	if ( '' === top && '' === right && '' === bottom && '' === left ) {
		return;
	}

	top = ( parseFloat( top ) != 0 && '' !== top ) ? parseFloat( top ) + unit + ' ' : '0 '; // eslint-disable-line eqeqeq
	right = ( parseFloat( right ) != 0 && '' !== right ) ? parseFloat( right ) + unit + ' ' : '0 '; // eslint-disable-line eqeqeq
	bottom = ( parseFloat( bottom ) != 0 && '' !== bottom ) ? parseFloat( bottom ) + unit + ' ' : '0 '; // eslint-disable-line eqeqeq
	left = ( parseFloat( left ) != 0 && '' !== left ) ? parseFloat( left ) + unit + ' ' : '0 '; // eslint-disable-line eqeqeq

	if ( right === left ) {
		left = '';

		if ( top === bottom ) {
			bottom = '';

			if ( top === right ) {
				right = '';
			}
		}
	}

	const output = top + right + bottom + left;

	return output.trim();
}


export const backgroundCss = (background) => {
	let css = '';
	let gradient = '';
	let image = '';
	for (const [key, value] of Object.entries(background)) {
		if (value) {
			if (key === 'gradient') {
				gradient = value;
			} else if (key === 'image') {
				image = value;
			} else if (key === 'position') {
				css += `background-${key}:${value[0] ? value[0] * 100 : 0}% ${
					value[1] ? value[1] * 100 : 0
				}% ;`;
			} else {
				css += `background-${key}:${value};`;
			}
		}
	}
	css +=
		image || gradient
			? `background-image:${
					image ? `url('${image.url}')${gradient ? `,` : ``}` : ``
			} ${gradient};`
			: ``;
	return css;
};

export const spaceSettingsCss = (space, type = 'padding') => {
	const spaceList = space;
	if (
		spaceList.top ||
		spaceList.top === 0 ||
		spaceList.right ||
		spaceList.right === 0 ||
		spaceList.bottom ||
		spaceList.bottom === 0 ||
		spaceList.left ||
		spaceList.left === 0
	) {
		if (
			(spaceList.top || spaceList.top === 0) &&
			(spaceList.right || spaceList.right === 0) &&
			(spaceList.bottom || spaceList.bottom === 0) &&
			(spaceList.left || spaceList.left === 0)
		) {
			return (
				`${type}:` + shorthandCSS(spaceList.top, spaceList.right, spaceList.bottom, spaceList.left, spaceList.unit) + `;`
			);
		}

		let css = ``;
		if ('' !== spaceList.top) {
			css += `${type}-top: ${spaceList.top}${spaceList.unit};`;
		}
		if ('' !== spaceList.bottom) {
			css += `${type}-bottom: ${spaceList.bottom}${spaceList.unit};`;
		}
		if ('' !== spaceList.left) {
			css += `${type}-left: ${spaceList.left}${spaceList.unit};`;
		}
		if ('' !== spaceList.right) {
			css += `${type}-right: ${spaceList.right}${spaceList.unit};`;
		}
		return css;
	}
	return '';
};

const borderRadiusCss = (borderSetting) => {
	const borderRadius = [
		borderSetting.radius,
		borderSetting['top-right'],
		borderSetting['bottom-right'],
		borderSetting['bottom-left'],
	];
	const allEqual = borderRadius.every((v) => v === borderRadius[0]);
	const unit = borderSetting.radius_unit ? borderSetting.radius_unit : 'px';
	let css = '';
	if (!allEqual) {
		css +=
			borderRadius[0] || 0 === borderRadius[0] || '0' === borderRadius[0]
				? `border-top-left-radius:${borderRadius[0]}${unit};`
				: ``;
		css +=
			borderRadius[1] || 0 === borderRadius[1] || '0' === borderRadius[1]
				? `border-top-right-radius:${borderRadius[1]}${unit};`
				: ``;
		css +=
			borderRadius[2] || 0 === borderRadius[2] || '0' === borderRadius[2]
				? `border-bottom-right-radius:${borderRadius[2]}${unit};`
				: ``;
		css +=
			borderRadius[3] || 0 === borderRadius[3] || '0' === borderRadius[3]
				? `border-bottom-left-radius:${borderRadius[3]}${unit};`
				: ``;
	} else {
		css = borderSetting.radius|| 0 === borderSetting.radius || '0' === borderSetting.radius ? `border-radius:${borderSetting.radius}${unit};` : '';
	}
	return css;
};

export const borderSettingsCss = (borderSetting) => {
	const unit = borderSetting.unit ? borderSetting.unit : 'px';
	const borderRadiusStyle = borderRadiusCss(borderSetting);

	const borderStyleValue = borderSetting.style ? borderSetting.style : '';

	let borderWidth = '';
	let borderColor = '';
	let borderStyle = ''
	
	if(
		( borderSetting.top || 0 === parseFloat( borderSetting.top ) ) &&
		( borderSetting.right || 0 === parseFloat( borderSetting.right ) ) &&
		( borderSetting.bottom || 0 === parseFloat( borderSetting.bottom ) ) &&
		( borderSetting.left || 0 === parseFloat( borderSetting.left ) ) ) {
		borderWidth = `border-width:${shorthandCSS( borderSetting.top, borderSetting.right, borderSetting.bottom, borderSetting.left, unit )};`
		borderStyle = borderSetting.style ? `border-style:${borderStyleValue};` : '';
	} else {
		if( borderSetting.top || 0 === parseFloat( borderSetting.top ) ) {
			borderWidth += `border-top-width:${borderSetting.top}${unit};`;
			borderStyle += borderStyleValue ? `border-top-style:${borderStyleValue};` : '';
		}
		if( borderSetting.right || 0 === parseFloat( borderSetting.right ) ) {
			borderWidth += `border-right-width:${borderSetting.right}${unit};`;
			borderStyle += borderStyleValue ? `border-right-style:${borderStyleValue};` : '';
		}
		if( borderSetting.bottom || 0 === parseFloat( borderSetting.bottom ) ) {
			borderWidth += `border-bottom-width:${borderSetting.bottom}${unit};`;
			borderStyle += borderStyleValue ? `border-bottom-style:${borderStyleValue};` : '';
		}
		if( borderSetting.left || 0 === parseFloat( borderSetting.left ) ) {
			borderWidth += `border-left-width:${borderSetting.left}${unit};`;
			borderStyle += borderStyleValue ? `border-left-style:${borderStyleValue};` : '';
		}
	}
	borderStyle += borderStyleValue && ! borderStyle ? `border-style:${borderStyleValue};` : '';

	
	if( borderSetting.color && borderSetting.color_right && borderSetting.color_bottom && borderSetting.color_left ) {
		if( ( borderSetting.color == borderSetting.color_bottom ) && ( borderSetting.color == borderSetting.color_right ) && ( borderSetting.color == borderSetting.color_left ) ) {
			borderColor = `border-color: ${borderSetting.color};`
		} else {
			borderColor = `border-color: ${borderSetting.color} ${borderSetting.color_right} ${borderSetting.color_bottom} ${borderSetting.color_left};`
		}
	}

	const borderColorTop    = ! borderColor && borderSetting.color ? `border-top-color: ${borderSetting.color};` : '';
	const borderColorRight  = ! borderColor && borderSetting.color_right ? `border-right-color: ${borderSetting.color_right};` : '';
	const borderColorBottom = ! borderColor && borderSetting.color_bottom ? `border-bottom-color: ${borderSetting.color_bottom};` : '';
	const borderColorLeft   = ! borderColor && borderSetting.color_left ? `border-left-color: ${borderSetting.color_left};` : '';
	borderColor += `${borderColorTop}${borderColorRight}${borderColorBottom}${borderColorLeft}`;
	

	return `${borderWidth}${borderColor}${borderStyle}${borderRadiusStyle}`;
};
 
/**
 * @param {Object} typoData accept font and text object
 * @param {string} type     accept css font and text
 * @param {Array}  skipProp exclude props
 * @return
 */
export function computeTypo(typoData = {}, type = 'font', skipProp = []) {
	let computeTypoProps = '';

	if (isEmpty(typoData)) {
		return computeTypoProps;
	}

	for (const key in typoData) {
		if (
			'sizeUnit' === key ||
			(Array.isArray(skipProp) && skipProp.includes(key))
		) {
			continue;
		}
		if ('size' === key) {
			if (isNaN(typoData[key])) {
				computeTypoProps += `${type}-${key}: ${typoData[key]};`;
			} else {
				computeTypoProps += `${type}-${key}: ${typoData[key]}${
					typoData.sizeUnit ? typoData.sizeUnit : 'px'
				};`;
			}
		} else if ('shadow' === key) {
			computeTypoProps += `${type}-${key}: ${typoData[key].x}px ${typoData[key].y}px ${typoData[key].blur}px ${typoData[key].color};`;
		} else {
			computeTypoProps += `${type}-${key}: ${typoData[key]};`;
		}
	}
	return computeTypoProps;
}

export const computeLetterStyle = ( data, cssSelector= 'letter-spacing' ) => {
	return `
	${
		data &&
		data.value >= 0
			? `${cssSelector}:${data.value}${
					data.unit
						? data.unit
						: 'px'
			};`
			: ``
	}`;
}