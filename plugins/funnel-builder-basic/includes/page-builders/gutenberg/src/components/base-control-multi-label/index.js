import './editor.scss';

const BaseControlMultiLabel = props => {
	let selectedUnit = props.unit;
	return (
		<div className="bwf-base-control-multi-label">
			<div className="bwf-base-control-multi-label__label components-base-control__label">{ props.label }</div>
			<div className="bwf-base-control-multi-label__units">
				{ props.units.length > 1 &&
					props.units.map( ( unit, i ) => {
						selectedUnit = selectedUnit ? selectedUnit : props.units[0];
						return (
							<button
								key={ i }
								className={ selectedUnit === unit ? 'is-active' : '' }
								onClick={ () => props.onChangeUnit( unit ) }
							>
								{ unit }
							</button>
						)
					} )
				}
				{ props.afterButton }
			</div>
		</div>
	)
}

BaseControlMultiLabel.defaultProps = {
	label: '',
	units: [ 'px' ],
	unit: 'px',
	onChangeUnit: () => {},
	afterButton: null,
}

export default BaseControlMultiLabel
