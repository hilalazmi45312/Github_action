const globalAttributes = {
	uniqueID: {
		type: 'string',
	},
	blockID: {
		type: 'string',
	},
	title: {
		type: 'string',
	},
	alignment: {
		type: 'object',
	},
	content: {
		type: 'string',
	},
	link: {
		type: 'string',
	},
	target: {
		type: 'string',
	},
	type: {
		type: 'string',
	},
	size: {
		type: 'string',
	},
	icon: {
		type: 'string',
	},
	button: {
		type: 'object',
	},
	htmlTag: {
		type: 'string',
	},
	layout: {
		type: 'string',
	},
	relation: {
		type: 'string',
	},
	screen: {
		type: 'string',
		default: 'desktop',
	},
	width: {
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
	minWidth: {
		type: 'object',
	},
	maxWidth: {
		type: 'object',
	},
	height: {
		type: 'object',
		default: {
			desktop: {
				height: '',
				unit: 'px',
			},
			tablet: {
				height: '',
				unit: 'px',
			},
			mobile: {
				height: '',
				unit: 'px',
			},
		},
	},
	minHeight: {
		type: 'object',
	},
	maxHeight: {
		type: 'object',
	},
	padding: {
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
	border: {
		type: 'object',
	},
	borderHover: {
		type: 'object',
	},
	margin: {
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
	background: {
		type: 'object',
	},
	backgroundHover: {
		type: 'object',
	},
	color: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	colorHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	zIndex: {
		type: 'object',
	},
	boxShadow: {
		type: 'object',
	},
	boxShadowHover: {
		type: 'object',
	},
	text: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	textHover: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
		},
	},
	lineHeight: {
		type: 'object',
	},
	lineHeightHover: {
		type: 'object',
	},
	letterSpacing: {
		type: 'object',
	},
	letterSpacingHover: {
		type: 'object',
	},
	font: {
		type: 'object',
		default: {
			desktop: {},
			tablet: {},
			mobile: {},
		},
	},
	fontHover: {
		type: 'object',
		default: {
			desktop: {},
			tablet: {},
			mobile: {},
		},
	},
	customAttribute: {
		type: 'array',
		default: [],
	},
	customCSS: {
		type: 'string',
		source: 'HTML',
	},
	hideBlock: {
		type: 'object',
		default: {
			desktop: '',
			tablet: '',
			mobile: '',
			loggedIn: '',
			loggedOut: '',
		},
	},
};

export default globalAttributes;
