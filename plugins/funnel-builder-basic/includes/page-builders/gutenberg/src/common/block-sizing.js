/**
 * External dependencies
 */
import { cloneDeep, defaultsDeep } from 'lodash';
/**
 * WordPress dependencies
 */
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { AdvancedRangeControl } from 'BWFOP/components';
import { getCurrentScreen } from 'BWFOP/utils/utils';


const BWFBlockSizing = (props) => {
    const { attributes: {width, minWidth, maxWidth, height, minHeight, maxHeight }, setAttributes } = props;

    const screen = getCurrentScreen();

    const onChangeSize = (newVal, updateKey, originalVal, attrKey) => {
        const tempKey = attrKey.match(/width/gi) ? 'width' : 'height';
        
        let updatedVal = JSON.parse( JSON.stringify(originalVal ? originalVal :  {
            [screen]: {
                [tempKey]:'',
                'unit':'px'
            }
        }));
        if ( ! updatedVal.hasOwnProperty(screen ) ) {
            updatedVal[screen] = {};
        }

        updatedVal[screen][updateKey] = newVal;
        setAttributes( { [attrKey]: updatedVal } );
    }
    return (
        <>
            <AdvancedRangeControl
                label={__('Width', 'funnel-builder-powerpack')}
                units={['px', 'em', '%', 'vw']}
                min={0}
                max={1600}
                step={1}
                allowReset={true}
                value={ width && width[screen] && width[screen].hasOwnProperty( 'width' ) && width[screen].width }
                unit={ width && width[screen] && width[screen].hasOwnProperty( 'unit' ) && width[screen].unit }
                onChange={(newVal) => onChangeSize(newVal, 'width', width, 'width' )}
                onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', width, 'width' )}
            />
            <AdvancedRangeControl
                label={__('Min Width', 'funnel-builder-powerpack')}
                units={['px', 'em', '%', 'vw']}
                min={0}
                max={1600}
                step={1}
                allowReset={true}
                value={ minWidth && minWidth[screen] && minWidth[screen].hasOwnProperty( 'width' ) && minWidth[screen].width }
                unit={ minWidth && minWidth[screen] && minWidth[screen].hasOwnProperty( 'unit' ) && minWidth[screen].unit }
                onChange={(newVal) => onChangeSize(newVal, 'width', minWidth, 'minWidth' )}
                onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', minWidth, 'minWidth' )}
            />
            <AdvancedRangeControl
                label={__('Max Width', 'funnel-builder-powerpack')}
                units={['px', 'em', '%', 'vw']}
                min={0}
                max={1600}
                step={1}
                allowReset={true}
                value={ maxWidth && maxWidth[screen] && maxWidth[screen].hasOwnProperty( 'width' ) && maxWidth[screen].width }
                unit={ maxWidth && maxWidth[screen] && maxWidth[screen].hasOwnProperty( 'unit' ) && maxWidth[screen].unit }
                onChange={(newVal) => onChangeSize(newVal, 'width', maxWidth, 'maxWidth' )}
                onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', maxWidth, 'maxWidth' )}
            />
            <AdvancedRangeControl
                label={__('Height', 'funnel-builder-powerpack')}
                units={['px', 'em', '%', 'vh']}
                min={0}
                max={1600}
                step={1}
                allowReset={true}
                value={ height && height[screen] && height[screen].hasOwnProperty( 'height' ) && height[screen].height }
                unit={ height && height[screen] && height[screen].hasOwnProperty( 'unit' ) && height[screen].unit }
                onChange={(newVal) => onChangeSize(newVal, 'height', height, 'height' )}
                onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', height, 'height' )}
            />
            <AdvancedRangeControl
                label={__('Min Height', 'funnel-builder-powerpack')}
                units={['px', 'em', '%', 'vh']}
                min={0}
                max={1600}
                step={1}
                allowReset={true}
                value={ minHeight && minHeight[screen] && minHeight[screen].hasOwnProperty( 'height' ) && minHeight[screen].height }
                unit={ minHeight && minHeight[screen] && minHeight[screen].hasOwnProperty( 'unit' ) && minHeight[screen].unit }
                onChange={(newVal) => onChangeSize(newVal, 'height', minHeight, 'minHeight' )}
                onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', minHeight, 'minHeight' )}
            />
            <AdvancedRangeControl
                label={__('Max Height', 'funnel-builder-powerpack')}
                units={['px', 'em', '%', 'vh']}
                min={0}
                max={1600}
                step={1}
                allowReset={true}
                value={ maxHeight && maxHeight[screen] && maxHeight[screen].hasOwnProperty( 'height' ) && maxHeight[screen].height }
                unit={ maxHeight && maxHeight[screen] && maxHeight[screen].hasOwnProperty( 'unit' ) && maxHeight[screen].unit }
                onChange={(newVal) => onChangeSize(newVal, 'height', maxHeight, 'maxHeight' )}
                onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', maxHeight, 'maxHeight' )}
            />
        </>
    )
}
BWFBlockSizing.defaultProps = {
    label: __( 'Sizing', 'funnel-builder-powerpack' ),
    initialOpen: false
}
export default BWFBlockSizing;
