/**
 * External dependencies
 */
import classNames from 'classnames';
import { isEmpty, defaultsDeep, noop, cloneDeep, merge } from 'lodash';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BaseControlMultiLabel from '../base-control-multi-label';
import BWFRangeControl from '../advanced-range-control/range-control';
import { DimensionToggle, getCurrentScreen } from 'BWFOP/utils/utils';
import { IconReset } from 'BWFOP/utils/images';

import './editor.scss';

const DimensionsControl = (props) => {
    const { attrVal={}, attrKey, setAttributes, label, type, onChange=noop, defaultSync=true } = props;

    const screen = getCurrentScreen();

    let unitChecker = '';

    const [syncValues, setSync] = useState(defaultSync);
    const [ initialVals, setInitialVals ] = useState(cloneDeep( defaultsDeep(attrVal, {
        [screen]: {
            [props.attrTop]: '',
            [props.attrRight]: '',
            [props.attrBottom]: '',
            [props.attrLeft]: '',
            [props.attrUnit]: 'px',
        },
    })));

    unitChecker = initialVals && initialVals[screen] && initialVals[screen][props.attrUnit]  ? initialVals[screen][props.attrUnit] : 'px';

    useEffect(() => {
        setInitialVals(merge(attrVal, initialVals));
    }, [attrVal])

    useEffect( () => {
        setInitialVals( cloneDeep( defaultsDeep(attrVal, {
            [screen]: {
                [props.attrTop]: '',
                [props.attrRight]: '',
                [props.attrBottom]: '',
                [props.attrLeft]: '',
                [props.attrUnit]: 'px',
            },
        }) ) );
        unitChecker = initialVals && initialVals[screen] && initialVals[screen][props.attrUnit] ? initialVals[screen][props.attrUnit] : 'px';
    }, [screen] )

    // Change All value Sync
    const handleSingleValue = (newValue) => {
        if ( 'padding' === type ) {
            // No negative values allowed here.
            newValue = newValue.toString().replace( /-/g, '' );
        }
        let updateVal = { ...initialVals };
        updateVal[screen][props.attrTop]    = newValue;
        updateVal[screen][props.attrRight]  = newValue;
        updateVal[screen][props.attrBottom] = newValue;
        updateVal[screen][props.attrLeft]   = newValue;
        if( setAttributes ) {
            setAttributes({ [props.attrKey]: updateVal });
        } else {
            onChange(updateVal);
        }
    };

    const onChangeDimValue = ( event, dimensionKey ) => {
        let newValue = event.target.value;

        if ( 'padding' === type ) {
            // No negative values allowed here.
            newValue = newValue.toString().replace( /-/g, '' );
        }
        let updateVal = { ...initialVals };
        updateVal[screen][dimensionKey] = newValue;
        if( setAttributes ) {
            setAttributes({ [props.attrKey]: updateVal });
        } else {
            onChange(updateVal);
        }

    };

    const onChangeUnit = (newUnit) => {
        let updateVal = { ...initialVals };
        updateVal[screen][props.attrUnit] = newUnit;
        if( setAttributes ) {
            setAttributes({ [props.attrKey]: updateVal });
        } else {
            onChange(updateVal);
        }
    }
    return (
        <div className={'bwf-dimensions-control-component'}>
            <BaseControlMultiLabel
                label={props.label}
                units={props.units}
                unit={initialVals[screen] && initialVals[screen][props.attrUnit]}
                onChangeUnit={onChangeUnit}
                afterButton={
                    <DimensionToggle initial={syncValues} onChange={ toggle => setSync(!syncValues) }/>
                }
            />
            {syncValues && (
                <BWFRangeControl
                    min={'padding' === props.type ? 0 : ( '%' === unitChecker ? -100 : props.min )}
                    max={ '%' === unitChecker ? 100 : props.max }
                    step={1}
                    initialPosition={0}
                    allowReset={true}
                    value={initialVals[screen] && initialVals[screen][props.attrTop]}
                    onChange={ (val) => handleSingleValue(val) }
                />
            )}
            {!syncValues &&
            <>
                <div className="components-bwf-dimensions-control__inputs">
                    <input
                        className="components-bwf-dimensions-control__number"
                        type="number"
                        onChange={ (e) => onChangeDimValue(e, props.attrTop) }
                        aria-label={ sprintf( __( '%s Top', 'funnel-builder-powerpack' ), label ) }
                        value={ initialVals[screen] && initialVals[screen][props.attrTop] }
                        min={'padding' === props.type ? 0 : ( '%' === unitChecker ? -100 : props.min )}
                        max={ '%' === unitChecker ? 100 : props.max }
                        data-attribute={ type }
                    />
                    <input
                        className="components-bwf-dimensions-control__number"
                        type="number"
                        onChange={ (e) => onChangeDimValue(e, props.attrRight) }
                        aria-label={ sprintf( __( '%s Top', 'funnel-builder-powerpack' ), label ) }
                        value={ initialVals[screen] && initialVals[screen][props.attrRight] }
                        min={'padding' === props.type ? 0 : ( '%' === unitChecker ? -100 : props.min )}
                        max={ '%' === unitChecker ? 100 : props.max }
                        data-attribute={ type }
                    />
                    <input
                        className="components-bwf-dimensions-control__number"
                        type="number"
                        onChange={ (e) => onChangeDimValue(e, props.attrBottom) }
                        aria-label={ sprintf( __( '%s Top', 'funnel-builder-powerpack' ), label ) }
                        value={ initialVals[screen] && initialVals[screen][props.attrBottom] }
                        min={'padding' === props.type ? 0 : ( '%' === unitChecker ? -100 : props.min )}
                        max={ '%' === unitChecker ? 100 : props.max }
                        data-attribute={ type }
                    />
                    <input
                        className="components-bwf-dimensions-control__number"
                        type="number"
                        onChange={ (e) => onChangeDimValue(e, props.attrLeft) }
                        aria-label={ sprintf( __( '%s Top', 'funnel-builder-powerpack' ), label ) }
                        value={ initialVals[screen] && initialVals[screen][props.attrLeft] }
                        min={'padding' === props.type ? 0 : ( '%' === unitChecker ? -100 : props.min )}
                        max={ '%' === unitChecker ? 100 : props.max }
                        data-attribute={ type }
                    />
                    <Button
                        className="components-bwf-dimensions-control_reset"
                        label={ __( 'Reset', 'funnel-builder-powerpack' ) }
                        onClick={ () => handleSingleValue( '' ) }
                        isSmall
                    >
                        { <IconReset /> }
                    </Button>

                </div>
                <div className="components-bwf-dimensions-control__input-labels">
                    <span className="components-bwf-dimensions-control__number-label">{ props.labelTop }</span>
                    <span className="components-bwf-dimensions-control__number-label">{ props.labelRight }</span>
                    <span className="components-bwf-dimensions-control__number-label">{ props.labelBottom }</span>
                    <span className="components-bwf-dimensions-control__number-label">{ props.labelLeft }</span>
                    <span className="components-bwf-dimensions-control__number-label"></span>
                </div>
            </>
            }
        </div>
    );
};

DimensionsControl.defaultProps = {
    label: __('Padding', 'funnel-builder-powerpack'),
    type: 'padding', // padding type has not accept Negative values
    units: ['px', 'em', '%'],
    unit: 'px',
    min: -1000,
    max: 1000,
    labelTop: __('Top', 'funnel-builder-powerpack'),
    labelRight: __('Right', 'funnel-builder-powerpack'),
    labelBottom: __('Bottom', 'funnel-builder-powerpack'),
    labelLeft: __('Left', 'funnel-builder-powerpack'),
    attrTop: 'top',
    attrRight: 'right',
    attrBottom: 'bottom',
    attrLeft: 'left',
    attrVal: {}, // main object contain { [screen]: { top:'', right:'', bottom:'', left:'', unit:'' } }
    attrKey: '',
    attrUnit: 'unit',
};

export default DimensionsControl;
