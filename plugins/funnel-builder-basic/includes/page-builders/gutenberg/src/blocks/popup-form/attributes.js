import { cloneDeep } from 'lodash';
import globalAttributes from '../../attributes';

export const attributes = {
	...globalAttributes,
	content: {
		type: 'string',
		default: 'Signup Now',
	},
	stackVertically: {
		type: 'object'
	},
	popupType: {
		type: 'string',
		default: 'bwf_pp_effect_fade',
	},
	closeVertical: {
		type: 'object',
		default: {
			desktop: {
				width: '-12',
				unit: 'px',
			},
			tablet: {
				width: '',
				unit: '',
			},
			mobile: {
				width: '',
				unit: '',
			},
		},
	},
	closeHorizontal: {
		type: 'object',
		default: {
			desktop: {
				width: '-12',
				unit: 'px',
			},
			tablet: {
				width: '',
				unit: '',
			},
			mobile: {
				width: '',
				unit: '',
			},
		},
	},
	progressBarText: {
		type: 'string',
		default: '75% Complete',
	},
	progressBarPadding: {
		type: 'object',
		default: {
			desktop: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			tablet: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			mobile: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
		},
	},
	progressBackground: {
		type: 'object',
		default: {},
	},
	progressBackgroundRemain: {
		type: 'object',
		default: {},
	},
	heading: {
		type: 'string',
		default: 'You\'re just one step away!',
	},
	textAfter: {
		type: 'string',
		default: 'Your Information is 100% Secure',
	},
	subHeading: {
		type: 'string',
		default: 'Enter your details below and we\'ll get you signed up',
	},
	type: {
		type: 'string',
		default: 'solid',
	},
	secondaryContentEnable: {
		type: 'boolean',
		default: false,
	},
	progressBarEnable: {
		type: 'boolean',
		default: false,
	},
	progressStripEnable: {
		type: 'boolean',
		default: false,
	},
	progressBarTextEnable: {
		type: 'boolean',
		default: false,
	},
	progressWidth: {
		type: 'int',
		default: 75,
	},
	progressHeight: {
		type: 'int',
		default: 40,
	},
	progressWidthTablet: {
		type: 'int',
	},
	progressHeightTablet: {
		type: 'int',
	},
	progressWidthMobile: {
		type: 'int',
	},
	progressHeightMobile: {
		type: 'int',
	},
	secondaryContent: {
		type: 'string',
		default: '',
	},
	secondaryFont: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryText: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryLineHeight: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryLetterSpacing: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryFontHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryTextHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryLineHeightHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryLetterSpacingHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	secondaryColorHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},

	headingFont: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	headingText: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	headingLineHeight: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	headingLetterSpacing: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	headingColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	headingColorHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},

	subHeadingFont: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingText: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingLineHeight: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingLetterSpacing: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingFontHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingTextHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingLineHeightHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingLetterSpacingHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	subHeadingColorHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	contentSpace: {
		type: 'object',
	},
	button: {
		type: 'object',
		default: {
			iconPos: 'left',
		},
	},
	buttonBackground: {
		type: 'object',
	},
	buttonBackgroundHover: {
		type: 'object',
	},
	buttonWidth: {
		type: 'object',
		default: {
			desktop: {
				width: '',
				unit: 'px',
			},
			tablet: {
				width: '',
				unit: 'px',
			},
			mobile: {
				width: '',
				unit: 'px',
			},
		},
	},
	buttonHeight: {
		type: 'object',
		default: {
			desktop: {
				width: '',
				unit: 'px',
			},
			tablet: {
				width: '',
				unit: 'px',
			},
			mobile: {
				width: '',
				unit: 'px',
			},
		},
	},
	paddingButton: {
		type: 'object',
		default: {
			desktop: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			tablet: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			mobile: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
		},
	},
	marginButton: {
		type: 'object',
		default: {
			desktop: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			tablet: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			mobile: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
		},
	},
	textAfterFont: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	textAfterText: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	textAfterLineHeight: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	textAfterLetterSpacing: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	textAfterColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	progressFont: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	progressText: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	progressLineHeight: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	progressLetterSpacing: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	progressColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	crossColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	crossColorHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	crossBackgroundColor: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	crossBackgroundColorHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	crossFont: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	crossBorder: {
		type: 'object',
	},
	crossPadding: {
		type: 'object',
		default: {
			desktop: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			tablet: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
			mobile: {
				top: '',
				right: '',
				bottom: '',
				left: '',
				unit: 'px',
			},
		},
	},
	buttonBoxShadow: {
		type: 'object',
	},
	buttonBoxShadowHover: {
		type: 'object',
	},
};
