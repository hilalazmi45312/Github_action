/**
 * Internal deepndencies
 */
import {
	backgroundCss,
	borderSettingsCss,
	getBoxShadowSettingCss,
	heightCss,
	spaceSettingsCss,
	widthCss,
	computeTypo,
	computeLetterStyle,
} from 'BWFOP/components/css';

import { minifyCSS, hasBlockVisible, getCurrentScreen } from 'BWFOP/utils/utils';

import { isEmpty } from 'lodash';

const editorStylePreview = (props, screen) => {
	let progressWidthKey = 'progressWidth';
	let progressHeightKey = 'progressHeight';
	if (screen && screen !== 'desktop') {
		progressWidthKey = screen === 'mobile' ? 'progressWidthMobile' : 'progressWidthTablet';
		progressHeightKey = screen === 'mobile' ? 'progressHeightMobile' : 'progressHeightTablet';
	}
	const {
		attributes: {
			color,
			colorHover,
			background,
			backgroundHover,
			border,
			borderHover,
			boxShadow,
			boxShadowHover,
			margin,
			padding,
			height,
			minHeight,
			maxHeight,
			width,
			minWidth,
			maxWidth,
			font,
			text,
			lineHeight,
			letterSpacing,
			fontHover,
			textHover,
			letterSpacingHover,
			lineHeightHover,

			uniqueID,
			secondaryContentEnable,
			secondaryFont,
			secondaryText,
			secondaryLineHeight,
			secondaryLetterSpacing,
			secondaryColor,
			secondaryColorHover,
			secondaryFontHover,
			secondaryTextHover,
			secondaryLetterSpacingHover,
			secondaryLineHeightHover,

			headingFont,
			headingText,
			headingLineHeight,
			headingLetterSpacing,
			headingColor,
			headingColorHover,
			headingFontHover,
			headingTextHover,
			headingLetterSpacingHover,
			headingLineHeightHover,

			subHeadingFont,
			subHeadingText,
			subHeadingLineHeight,
			subHeadingLetterSpacing,
			subHeadingColor,

			subHeadingFontHover,
			subHeadingTextHover,
			subHeadingLineHeightHover,
			subHeadingLetterSpacingHover,
			subHeadingColorHover,

			progressBackground,
			progressBackgroundHover,
			progressBackgroundRemain,
			contentSpace,
			[progressHeightKey]: progressHeight,
			[progressWidthKey]: progressWidth,
			button,
			buttonBackground,
			buttonBackgroundHover,
			buttonWidth,
			buttonHeight,
			paddingButton,
			marginButton,
			alignment,

			progressColor,
			progressFont,
			progressText,
			progressLineHeight,
			progressLetterSpacing,
			progressBarPadding,

			crossColor,
			crossColorHover,
			crossBackgroundColor,
			crossBackgroundColorHover,
			crossFont,
			crossBorder,
			crossPadding,
			closeVertical,
			closeHorizontal,

			textAfterColor,
			textAfterFont,
			textAfterText,
			textAfterLineHeight,
			textAfterLetterSpacing,

			buttonBoxShadow,
			buttonBoxShadowHover,
		},
	} = props;
	const uniqueClass = `body .bwfop-popup-form-container.bwf-${uniqueID}`;

	const buttonCss = `${uniqueClass} .bwf-btn-wrap.bwfop-poup-button-wrap .bwf-btn`;

	const popUp = `${uniqueClass} .bwfop-popup-bg.bwf-popup-${uniqueID}`;
	/** Button spacing b/w text and secondary text  */
	const secBtnGap =
		contentSpace && contentSpace.value
			? `margin-top: ${contentSpace.value}${contentSpace.unit || 'px'};`
			: '';

	const letterSpacingStyle = (data) => {
		if (!(data && data.value)) {
			return '';
		}
		const LSUnit = data.unit ? data.unit : 'px';

		return `letter-spacing: ${data.value}${LSUnit};`;
	};

	const btnIconStyle = () => {
		if (isEmpty(button) || isEmpty(button.icon)) {
			return '';
		}
		let btnStyle = '';
		btnStyle += button && button.hasOwnProperty('iconSize')
			? `width: ${button.iconSize}px; height:${button.iconSize}px;`
			: '';
		return btnStyle;
	};

	const typoColor = (color, font, text, letterSpacing, lineHeight) => {
		return `
		${color && color[screen]
				? `color: ${color[screen]};`
				: ``
			}
		${letterSpacing && letterSpacing[screen]
				? letterSpacingStyle(
					letterSpacing[screen]
				)
				: ''
			}
		${font && font[screen] ? computeTypo(font[screen], 'font', ['sizeUnit']) : ''}
		${text && text[screen] ? computeTypo(text[screen], 'text') : ''}
		${lineHeight && lineHeight[screen]
				? computeLetterStyle(lineHeight[screen], 'line-height')
				: ``
			}`;
	}

	return (`
		
		${uniqueClass} .bwf-btn-wrap.bwfop-poup-button-wrap{
			${alignment && alignment[screen] ? `text-align: ${alignment[screen]};` : ''}
		}
		${btnIconStyle()
			? `${buttonCss} .bwf-icon-inner-svg svg{
						${btnIconStyle()}
					}`
			: ''
		}
		${popUp} .bwf-poup-inner-wrap{
			${padding && padding[screen] ? spaceSettingsCss(padding[screen], 'padding') : ''}
			${width && width[screen] ? widthCss(width[screen]) : ``}
			${minWidth && minWidth[screen]
			? widthCss(minWidth[screen], 'min-width')
			: ``
		} 
			${maxWidth && maxWidth[screen]
			? widthCss(maxWidth[screen], 'max-width')
			: ``
		} 
			${height && height[screen] ? heightCss(height) : ``}
			${minHeight && minHeight[screen]
			? heightCss(minHeight[screen], 'min-height')
			: ``
		} 
			${maxHeight && maxHeight[screen]
			? heightCss(maxHeight[screen], 'max-height')
			: ``
		}
		}
		${popUp} .bwf-poup-inner-wrap{
			${boxShadow && boxShadow[screen] ? `box-shadow:${getBoxShadowSettingCss(boxShadow[screen])};` : ''}
			${background && background[screen] ? backgroundCss(background[screen]) : ''}
		}

		${popUp} .bwf-poup-inner-wrap:hover{
			${boxShadowHover && boxShadowHover[screen] ? `box-shadow:${getBoxShadowSettingCss(boxShadowHover[screen])};` : ''}
			${backgroundHover && backgroundHover[screen] ? backgroundCss(backgroundHover[screen]) : ''}
		}
		${popUp} .bwf_pp_close{
			${closeVertical && closeVertical[screen] && closeVertical[screen]['width'] ? `top:${closeVertical[screen]['width']}${closeVertical[screen]['unit'] ? closeVertical[screen]['unit'] : 'px'};` : ``}
			${closeHorizontal && closeHorizontal[screen] && closeHorizontal[screen]['width'] ? `right:${closeHorizontal[screen]['width']}${closeHorizontal[screen]['unit'] ? closeHorizontal[screen]['unit'] : 'px'};` : ``}
			${crossFont && crossFont[screen] && crossFont[screen].size ? `width:${crossFont[screen].size}${crossFont[screen].unit ? crossFont[screen].unit : 'px'};height:${crossFont[screen].size}${crossFont[screen].unit ? crossFont[screen].unit : 'px'};` : ''}
			${typoColor(crossColor, crossFont, null, null, null)}
			${crossBackgroundColor && crossBackgroundColor[screen] ? backgroundCss(crossBackgroundColor[screen]) : ''}
			${crossPadding && crossPadding[screen] ? spaceSettingsCss(crossPadding[screen], 'padding') : ''}
			${crossBorder && crossBorder[screen] ? borderSettingsCss(crossBorder[screen]) : ''}
		}
		${popUp} .bwf_pp_close:hover{
			${typoColor(crossColorHover, null, null, null, null)}
			${crossBackgroundColorHover && crossBackgroundColorHover[screen] ? backgroundCss(crossBackgroundColorHover[screen]) : ''}
		}
		${popUp} .bwf-poup-inner-wrap .bwf-popup-head .pp-bar-text{
			${typoColor(progressColor, progressFont, progressText, progressLetterSpacing, progressLineHeight)}
		}
		${popUp} .bwf-heading-wrap .bwf-heading{
			${typoColor(headingColor, headingFont, headingText, headingLetterSpacing, headingLineHeight)}
		}
		${popUp} .bwf-heading-wrap .bwf-heading:hover{
			${typoColor(headingColorHover, headingFontHover, headingTextHover, headingLetterSpacingHover, headingLineHeightHover)}
		}

		${popUp} .bwf-heading-wrap .bwf-subheading{
			${typoColor(subHeadingColor, subHeadingFont, subHeadingText, subHeadingLetterSpacing, subHeadingLineHeight)}
		}
		${popUp} .bwf-heading-wrap .bwf-subheading:hover{
			${typoColor(subHeadingColorHover, subHeadingFontHover, subHeadingTextHover, subHeadingLetterSpacingHover, subHeadingLineHeightHover)}
		}
		${popUp} .bwf-pp-footer {
			${typoColor(textAfterColor, textAfterFont, textAfterText, textAfterLetterSpacing, textAfterLineHeight)}
		}
		${popUp} .bwf-pp-bar-wrap .bwf-pp-bar{
			${progressBackground && progressBackground[screen] ? backgroundCss(progressBackground[screen]) : ''}
			${progressWidth !== '' ? `width:${progressWidth}%;` : ``}
		}
		${popUp} .bwf-pp-bar-wrap {
			${progressBarPadding && progressBarPadding[screen] ? spaceSettingsCss(progressBarPadding[screen], 'padding') : ''}
			${progressHeight ? `height:${progressHeight}px;` : ``}
			${progressBackgroundRemain && progressBackgroundRemain[screen] ? backgroundCss(progressBackgroundRemain[screen]) : ''}
		}
		${buttonCss} .bwf-icon-inner-svg{
			${button && button.hasOwnProperty('iconSpace') ? (!!button && !!button.icon && 'left' === button.iconPos ? `margin-right: ${button.iconSpace}px;` : `margin-left: ${button.iconSpace}px;`) : ''
		}
		}
		${buttonCss} .bwf-icon-inner-svg, ${buttonCss} .bwf-btn-inner-text {
			${typoColor(color, font, text, letterSpacing, lineHeight)}
			${text && text[screen] && text[screen].align ? `justify-content:${text[screen].align};` : ``}
		}
		${buttonCss} {
			${buttonBackground && buttonBackground[screen] ? backgroundCss(buttonBackground[screen]) : ''}
			${buttonWidth && buttonWidth[screen] ? widthCss(buttonWidth[screen]) : ``}
			${buttonHeight && buttonHeight[screen] ? heightCss(buttonHeight[screen]) : ``}
			${border && border[screen] ? borderSettingsCss(border[screen]) : ''}
			${marginButton && marginButton[screen] ? spaceSettingsCss(marginButton[screen], 'margin') : ''}
			${paddingButton && paddingButton[screen] ? spaceSettingsCss(paddingButton[screen], 'padding') : ''}

			${buttonBoxShadow && buttonBoxShadow[screen] ? `box-shadow:${getBoxShadowSettingCss(buttonBoxShadow[screen])};` : ''}
		}
		${buttonCss}:hover{
			${buttonBackgroundHover && buttonBackgroundHover[screen] ? backgroundCss(buttonBackgroundHover[screen]) : ''}

			${buttonBoxShadowHover && buttonBoxShadowHover[screen] ? `box-shadow:${getBoxShadowSettingCss(buttonBoxShadowHover[screen])};` : ''}
		}
		${buttonCss}:hover .bwf-icon-inner-svg, ${buttonCss}:hover .bwf-btn-inner-text {
			${typoColor(colorHover, fontHover, textHover, letterSpacingHover, lineHeightHover)}
			${textHover && textHover[screen] && textHover[screen].align ? `justify-content:${textHover[screen].align};` : ``}
		}
		${secondaryContentEnable
			? `${buttonCss} .bwf-btn-sub-text {		
						${typoColor(secondaryColor, secondaryFont, secondaryText, secondaryLetterSpacing, secondaryLineHeight)}
						${secBtnGap}
					}
			${buttonCss}:hover .bwf-btn-sub-text{
				${typoColor(secondaryColorHover, secondaryFontHover, secondaryTextHover, secondaryLetterSpacingHover, secondaryLineHeightHover)}
			}`
			: ''
		}
		
	`);
};

const BWFBlockStyle = (props) => {
	const screen = getCurrentScreen();
	return (
		<>
			<style>
				{minifyCSS(editorStylePreview(props, 'desktop'))}
			</style>
			{
				('tablet' === screen || 'mobile' === screen) &&
				<style>
					{minifyCSS(editorStylePreview(props, 'tablet'))}
				</style>
			}
			{
				'mobile' === screen &&
				<style>
					{minifyCSS(editorStylePreview(props, screen))}
				</style>
			}

		</>
	);
};

export default BWFBlockStyle;
