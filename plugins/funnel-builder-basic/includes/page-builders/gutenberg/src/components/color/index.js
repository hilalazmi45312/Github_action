/**
 * External dependencies
 */
import { map } from 'lodash';
import tinycolor from 'tinycolor2';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { ColorIndicator, ColorPicker } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CircularOptionPicker from './circular-option-picker';
import { defaultColors, rgba2hex } from 'BWFOP/utils/utils';

function SingleColorPalette({
	clearable = true,
	className,
	colors,
	disableCustomColors = false,
	onChange,
	value,
	isLast,
}) {
	const clearColor = useCallback(() => onChange(undefined), [onChange]);

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
		<CircularOptionPicker
			className={className}
			options={colorOptions}
			actions={
				isLast ?
				<>
					{!disableCustomColors && (
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
				</> : null
			}
		/>
	);
}

export default function ColorPalette(props){
	const { label, value, colors = defaultColors } = props;
	const {colors: themeColors} = useSelect(blockEditorStore).getSettings();
	const uniqueColors = themeColors.filter((color) =>  colors.findIndex((c) => c.color === color.color) < 0)
	const palletes = [
		...(
			uniqueColors.length > 1 ? [
				{label: 'THEME', colors: uniqueColors}
			] : []
		),
		{ label: 'DEFAULT', colors: colors}
	]


    /** Generate Unique ID for each BWFColorPalette Component */
	const id = `inspector-bwf-color-palette-${ useInstanceId( ColorPalette ) }`;

	return (
        <div className={'bwf-color-pallete-component'}>
            {
				/** Label For BWFColorPalette */
				label &&( <label className="components-label components-color-pallete-label" htmlFor={ id } style={ {marginBottom:'15px', display: 'block', textAlign: 'left'} }>
                    { label }
                    <ColorIndicator	className="bwf-color-indicator"	colorValue={value}/>
                </label> )
			}
			<div className={'bwf-color-pallete-component-inner-wrap'}>
				{palletes.map((pallete, i) => (
					<div className={classNames({'bwf-mb10': i !== palletes.length - 1})} key={`bwf-multi-color-pallete-${i}`}>
						<label className='bwf-label'>{pallete.label}</label>
						<SingleColorPalette {...props} colors={pallete.colors} isLast={i === palletes.length - 1} />
					</div>
				))}
			</div>
		</div>
	)
}