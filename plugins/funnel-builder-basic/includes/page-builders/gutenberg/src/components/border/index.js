/**
 * Wordpress Dependencies
 */
import {
	Button,
	RangeControl,
	SelectControl,
	CardDivider,
	ColorIndicator,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * External Dependencies
 */
import classNames from 'classnames';
import { noop, defaultsDeep, cloneDeep, merge } from 'lodash';

/**
 * Internal Dependencies
 */
import { colors } from '../background';
import ColorPalette from 'BWFOP/components/color';
import { DimensionsControl } from 'BWFOP/components';
import { getCurrentScreen } from 'BWFOP/utils/utils'
import { DimensionToggle } from 'BWFOP/utils/utils';


const BorderColorList = [
	{
		id: 'color',
		label: __('Top', 'funnel-builder-powerpack'),
		selectindex: 0,
		type: 'color',
	},
	{
		id: 'color_bottom',
		label: __('Bottom', 'funnel-builder-powerpack'),
		selectindex: 1,
		type: 'color',
	},
	{
		id: 'color_left',
		label: __('Left', 'funnel-builder-powerpack'),
		selectindex: 2,
		type: 'color',
	},
	{
		id: 'color_right',
		label: __('Right', 'funnel-builder-powerpack'),
		selectindex: 3,
		type: 'color',
	},
];

const BorderComponenet = (props) => {
	const { border, onChange = noop, disable=[] } = props;
	const screen = getCurrentScreen();
	const [borderColorSelect, changeBorderColorSelect] = useState('color');
	const [syncValues, setSync] = useState(true);
	const [ initialVals, setInitialVals ] = useState(cloneDeep( defaultsDeep(border, {
        [screen]: {
            'top': '',
			'bottom': '',
			'left': '',
			'right': '',
			'unit': 'px',
			'style': '',
			'color': ''
        },
    })));

	useEffect(() => {
        setInitialVals(merge(border, initialVals));
    }, [border])

	useEffect( () => {
        setInitialVals( cloneDeep( defaultsDeep(border, {
            [screen]: {
                'top': '',
				'bottom': '',
				'left': '',
				'right': '',
				'unit': 'px',
				'style': '',
				'color': ''
            },
      }) ) );
    }, [screen] );
	const borderStyle = initialVals[screen] && initialVals[screen]['style'] ? initialVals[screen]['style'] : '';

	return (
		<div style={{ width: '100%' }}>
			{
				disable.includes( 'border-style' ) || (<>

					<SelectControl
						label={__('Border Style', 'funnel-builder-powerpack')}
						value={ borderStyle }
						style={{ width: '100%' }}
						options={[
							{
								label: __('Not Set', 'funnel-builder-powerpack'),
								value: '',
							},
							{
								label: __('None', 'funnel-builder-powerpack'),
								value: 'none',
							},
							{
								label: __('Solid', 'funnel-builder-powerpack'),
								value: 'solid ',
							},
							{
								label: __('Dotted', 'funnel-builder-powerpack'),
								value: 'dotted',
							},
							{
								label: __('Double', 'funnel-builder-powerpack'),
								value: 'double',
							},
							{
								label: __('Inset', 'funnel-builder-powerpack'),
								value: 'inset ',
							},
							{
								label: __('Outset', 'funnel-builder-powerpack'),
								value: 'outset',
							},
							{
								label: __('Ridge', 'funnel-builder-powerpack'),
								value: 'ridge',
							},
							{
								label: __('Dashed', 'funnel-builder-powerpack'),
								value: 'dashed',
							},
						]}
						onChange={(style) => {
							let updateVal = { ...initialVals };
							updateVal[screen].style = style;
							onChange(updateVal);
						}}
					/>
					<CardDivider />
				</>)
			}
			{
				( disable.includes( 'border-width' ) || [null, '', 'none'].includes(borderStyle) ) || (<>
					<DimensionsControl
						label={ __('Border Width', 'funnel-builder-powerpack') }
						min={0}
						max={50}
						step={1}
						attrVal={ border }
						onChange={ (newVal) => { onChange(newVal) } }
					/>

					<CardDivider />
				</>)
			}
			{
				( disable.includes( 'border-color' ) || [null, '', 'none'].includes(borderStyle) ) || (<>
					<label htmlFor="bwf-border" className="bwf-label bwf-flex-center">
						{__('Border Color', 'funnel-builder-powerpack')}
						<DimensionToggle initial={syncValues} onChange={ toggle => setSync(toggle) }/>
					</label>
					<div
						role="group"
						className="components-button-group block-editor-color-gradient-control__button-tabs mb-10"
					>
						{!syncValues ? BorderColorList.map((colorlist) => (
								<Button
									type="button"
									aria-pressed="false"
									key={colorlist.id}
									className={classNames('components-button is-small', {
										'is-pressed':
											borderColorSelect === colorlist.id
												? true
												: false,
									})}
									onClick={() => changeBorderColorSelect(colorlist.id)}
								>
									{colorlist.label}
								</Button>
							)) :
							<Button
								type="button"
								aria-pressed="false"
								className={classNames('components-button is-small', {
									'is-pressed':true})}
							>
								{__('All', 'funnel-builder-powerpack' )}
							</Button>
						}
						<ColorIndicator
							className="bwf-color-indicator"
							colorValue={initialVals[screen] && initialVals[screen][borderColorSelect]}
						/>
					</div>
					<ColorPalette
						value={initialVals[screen] && initialVals[screen][borderColorSelect]}
						onChange={(newColor) => {
							let updateVal = { ...initialVals };
							if ( !syncValues ) {
								updateVal[screen][borderColorSelect] = newColor;
							} else {
								updateVal[screen]['color'] = newColor;
								updateVal[screen]['color_bottom'] = newColor;
								updateVal[screen]['color_left'] = newColor;
								updateVal[screen]['color_right'] = newColor;
							}
							onChange(updateVal);
						}}
						disableCustomColors={false}
						colors={colors}
						clearable={true}
					/>
					<CardDivider />

				</>)
			}

			{
				disable.includes( 'border-radius' ) || (<>
					<DimensionsControl
						label={ __('Border Radius', 'funnel-builder-powerpack') }
						min={0}
						max={50}
						step={1}
						attrVal={ border }
						onChange={ (newVal) => { onChange(newVal) } }
						labelTop={ __( 'T Left' ) }
						labelRight={ __( 'T Right' ) }
						labelBottom={ __( 'B Left' ) }
						labelLeft={ __( 'B Right' ) }
						attrTop='radius'
						attrRight='top-right'
						attrBottom='bottom-left'
						attrLeft='bottom-right'
						attrUnit='radius_unit'
					/>

				</>)
			}

		</div>
	);
};

export default BorderComponenet;