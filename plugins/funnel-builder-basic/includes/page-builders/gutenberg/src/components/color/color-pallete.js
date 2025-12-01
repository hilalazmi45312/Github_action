/**
 * External dependencies
 */
import { map } from 'lodash';
import tinycolor from 'tinycolor2';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useMemo } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { ColorPicker, ColorIndicator } from '@wordpress/components';
import CircularOptionPicker from './circular-option-picker';
import { rgba2hex } from 'BWFOP/utils/utils';

const BWFColorPalette = ( props ) => {
    const {
        clearable = true,
        className,
        colors,
        disableCustomColors = false,
        onChange,
        value,
        label
    } = props;
    const clearColor = useCallback(() => onChange(undefined), [onChange]);

    /** Generate Unique ID for each BWFColorPalette Component */
	const id = `inspector-bwf-color-palette-${ useInstanceId( BWFColorPalette ) }`;

     const colorOptions = useMemo(() => {
         return map(colors, ({ color, name }) => (
             <CircularOptionPicker.Option
                 key={color}
                 isSelected={value === color}
                 selectedIconProps={
                     value === color
                         ? {
                                 fill: tinycolor
                                     .mostReadable(color, ['#000', '#fff'])
                                     .toHexString(),
                           }
                         : {}
                 }
                 tooltipText={
                     name ||
                     // translators: %s: color hex code e.g: "#f00".
                     sprintf(__('Color code: %s'), color)
                 }
                 style={{ backgroundColor: color, color }}
                 onClick={value === color ? clearColor : () => onChange(color)}
                 aria-label={
                     name
                         ? // translators: %s: The name of the color e.g: "vivid red".
                           sprintf(__('Color: %s'), name)
                         : // translators: %s: color hex code e.g: "#f00".
                           sprintf(__('Color code: %s'), color)
                 }
             />
         ));
     }, [colors, value, onChange, clearColor]);
     const renderCustomColorPicker = () => (
         <ColorPicker
             color={value}
             onChangeComplete={(value) => {
                 onChange(
                     rgba2hex(
                         'rgba(' +
                             value.rgb.r +
                             ',' +
                             value.rgb.g +
                             ',' +
                             value.rgb.b +
                             ',' +
                             value.rgb.a +
                             ')'
                     )
                 );
             }}
             disableAlpha={false}
         />
     );
 
     return (
        <div className={'bwf-color-pallete-component'}>
            {
				/** Label For BWFColorPalette */
				label &&( <label className="components-label components-color-pallete-label" htmlFor={ id } style={ {marginBottom:'15px', display: 'block'} }>
                    { label }
                    <ColorIndicator	className="bwf-color-indicator"	colorValue={value}/>
                </label> )
			}
            <CircularOptionPicker
                className={className}
                options={colorOptions}
                actions={
                    <>
                        { ! disableCustomColors && (
                            <CircularOptionPicker.DropdownLinkAction
                                dropdownProps={{
                                    renderContent: renderCustomColorPicker,
                                    contentClassName:
                                        'components-color-palette__picker',
                                }}
                                buttonProps={{
                                    'aria-label': __('Custom color picker'),
                                }}
                                linkText={__('Custom color')}
                                className={`components-button is-link`}
                            />
                        )}
                        {!!clearable && (
                            <CircularOptionPicker.ButtonAction
                                onClick={clearColor}
                                className={`components-circular-option-picker__clear is-secondary is-small`}
                            >
                                {__('Clear')}
                            </CircularOptionPicker.ButtonAction>
                        )}
                    </>
                }
            />

        </div>
     );
}
export default BWFColorPalette;