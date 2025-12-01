/**
 * Internal dependencies
 */
import BaseControlMultiLabel from '../base-control-multi-label'
import RangeControl from './range-control'

/**
 * WordPress dependencies
 */
import { BaseControl } from '@wordpress/components'

/**
 * External dependencies
 */
import classnames from 'classnames'
import { omit } from 'lodash'

export const convertToNumber = value => {
	if ( typeof value === 'string' && value !== '' && value.match( /^[\d.]+$/ ) ) {
		return value.includes( '.' ) ? parseFloat( value ) : parseInt( value, 10 )
	}
	return value
}

const AdvancedRangeControl = props => {
	const propsToPass = { ...omit( props, [ 'className', 'help', 'label', 'units', 'unit', 'onChangeUnit' ] ) }

	const classNames = classnames( [
		'bwf-advanced-range-control',
		props.className,
	] )

	return (
		<BaseControl
			help={ props.help }
			className={ classNames }
		>
			<BaseControlMultiLabel
				label={ props.label }
				units={ props.units }
				unit={ props.unit }
				onChangeUnit={ props.onChangeUnit }
			/>
			<RangeControl
				{ ...propsToPass }
			/>
		</BaseControl>
	)
}

AdvancedRangeControl.defaultProps = {
	onChange: () => {},
	onChangeUnit: () => {},
	help: '',
	className: '',
	units: [ 'px' ],
	unit: 'px',
	placeholder: '',
	initialPosition: '',
	min: 0,
	max: 100,
}

export default AdvancedRangeControl
