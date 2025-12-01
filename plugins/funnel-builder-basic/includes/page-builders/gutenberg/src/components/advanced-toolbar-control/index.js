/**
 * Internal dependencies
 */
import BaseControlMultiLabel from '../base-control-multi-label'
import SVGIconBottom from './icons/bottom.svg'
import SVGIconHorizontalCenter from './icons/horizontal-center.svg'
import SVGIconLeft from './icons/left.svg'
import SVGIconRight from './icons/right.svg'
import SVGIconStretch from './icons/stretch.svg'
import SVGIconTop from './icons/top.svg'
import SVGIconVerticalCenter from './icons/vertical-center.svg'
import './editor.scss'

/**
 * External dependencies
 */
import classnames from 'classnames'

/**
 * WordPress dependencies
 */
import {
	BaseControl, ButtonGroup, Button
} from '@wordpress/components'
import { __ } from '@wordpress/i18n'

const FLEX_HORIZONTAL_ALIGN_OPTIONS = [
	{
		value: 'flex-start',
		title: __( 'Align Left', 'funnel-builder-powerpack' ),
		icon: <SVGIconLeft />,
	},
	{
		value: 'center',
		title: __( 'Align Center', 'funnel-builder-powerpack' ),
		icon: <SVGIconHorizontalCenter />,
	},
	{
		value: 'flex-end',
		title: __( 'Align Right', 'funnel-builder-powerpack' ),
		icon: <SVGIconRight />,
	},
]

const FLEX_VERTICAL_ALIGN_STRETCH_OPTIONS = [
	{
		value: 'flex-start',
		title: __( 'Align Top', 'funnel-builder-powerpack' ),
		icon: <SVGIconTop />,
	},
	{
		value: 'center',
		title: __( 'Align Center', 'funnel-builder-powerpack' ),
		icon: <SVGIconVerticalCenter />,
	},
	{
		value: 'flex-end',
		title: __( 'Align Bottom', 'funnel-builder-powerpack' ),
		icon: <SVGIconBottom />,
	},
	{
		value: 'stretch',
		title: __( 'Stretch', 'funnel-builder-powerpack' ),
		icon: <SVGIconStretch />,
	},
]

const FLEX_VERTICAL_ALIGN_OPTIONS = [
	{
		value: 'flex-start',
		title: __( 'Align Top', 'funnel-builder-powerpack' ),
		icon: <SVGIconTop />,
	},
	{
		value: 'center',
		title: __( 'Align Center', 'funnel-builder-powerpack' ),
		icon: <SVGIconVerticalCenter />,
	},
	{
		value: 'flex-end',
		title: __( 'Align Bottom', 'funnel-builder-powerpack' ),
		icon: <SVGIconBottom />,
	},
]

const CONTROLS = {
	'horizontal': FLEX_HORIZONTAL_ALIGN_OPTIONS,
	'vertical': FLEX_VERTICAL_ALIGN_OPTIONS,
	'vertical-stretch': FLEX_VERTICAL_ALIGN_STRETCH_OPTIONS,
}

const AdvancedToolbarControl = props => {
	const controls = typeof props.controls === 'string' ? CONTROLS[ props.controls ] : props.controls

	const toolbarClasses = classnames( {
		'bwf-toolbar--full-width': props.fullwidth,
		'bwf-toolbar--multiline': props.multiline,
	} )

	return (
		<BaseControl
			help={ props.help }
			className={ classnames( 'bwf-advanced-toolbar-control', props.className ) }
		>
			<BaseControlMultiLabel
				label={ props.label }
				units={ props.units }
				unit={ props.unit }
				onChangeUnit={ props.onChangeUnit }
			/>
			<ButtonGroup
				children={
					controls.map( ( option, index ) => {
						const controlProps = {
							...option,
							onClick: () => {
								// If toggle only, prevent buttons from being unselected.
								if ( props.isToggleOnly && option.value === props.value ) {
									return
								}
								props.onChange( option.value !== props.value ? option.value : '' )
							},
							isPrimary: props.value === option.value,
							isSmall: props.isSmall,
							children: ! option.icon ? option.custom || <span className="bwf-advanced-toolbar-control__text-button">{ option.title }</span> : null,
						}
						return <Button key={ index } { ...controlProps } />
					} )
				}
				className={ toolbarClasses }
			/>
		</BaseControl>
	)
}

AdvancedToolbarControl.defaultProps = {
	onChange: () => {},
	onChangeUnit: () => {},
	help: '',
	className: '',
	units: [ 'px' ],
	unit: 'px',
	screens: [ 'desktop' ],
	value: '',
	controls: [],
	multiline: false,
	fullwidth: true,
	isSmall: false,
	isToggleOnly: false,
}

export default AdvancedToolbarControl
