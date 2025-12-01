/**
 * External depedencies
 */
import Select from 'react-select';
import lodash from 'lodash';
import { cloneDeep } from 'lodash';
/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import {
	CardDivider,
	ColorPicker,
	Button,
	Popover,
	ToggleControl,
} from '@wordpress/components';
/**
 * Internal dependencies
 */
import { TextSettings } from '../text';
import FontSettings from '../font';
import './style.scss';
import { rgba2hex } from 'BWFOP/utils/utils';
import { AdvancedRangeControl, AlignButtonsControl } from 'BWFOP/components';
import { IconReset } from '../../utils/images';

const shadowOptions = [
	{
		id: 'h-offset',
		selectindex: 0,
		label: __('H Offset', 'funnel-builder-powerpack'),
	},
	{
		id: 'v-offset',
		selectindex: 1,
		label: __('V Offests', 'funnel-builder-powerpack'),
	},
	{
		id: 'blur',
		selectindex: 2,
		label: __('Blur', 'funnel-builder-powerpack'),
	},
	{
		id: 'spread',
		selectindex: 3,
		label: __('Spread', 'funnel-builder-powerpack'),
	},
	{
		id: 'color',
		selectindex: 4,
		label: __('Color', 'funnel-builder-powerpack'),
		type: 'color',
	},
	{
		id: 'inset',
		selectindex: 5,
		label: __('Enable Box Shadow Inset', 'funnel-builder-powerpack'),
		type: 'toggle',
	},
];

export const BwfColorPicker = (props) => {
	const { color, onChange } = props;
	const [colorPopup, showColorPopup] = useState(false);

	return (
		<Fragment>
			<Button
				className="bwf-color"
				style={{ backgroundColor: color }}
				onClick={() => showColorPopup(!colorPopup)}
			></Button>
			{colorPopup ? (
				<Popover
					position="bottom right"
					onClose={() => { }}
					onFocusOutside={() => {
						showColorPopup(false);
					}}
				>
					<div className="bwf-blockcontrol-wrap">
						<div className="pd-10">
							<ColorPicker
								color={color}
								disableAlpha={false}
								onChangeComplete={(value) => {
									value.hex = rgba2hex(
										'rgba(' +
										value.rgb.r +
										',' +
										value.rgb.g +
										',' +
										value.rgb.b +
										',' +
										value.rgb.a +
										')'
									);
									onChange(value);
								}}
							/>
						</div>
					</div>
				</Popover>
			) : null}
		</Fragment>
	);
};

export const FieldSettings = (props) => {
	const { typography, setTypography, settingOptions, title } = props;
	return (
		<div className="bwf-popover-wrapper">
			{title && <strong className="bwf-heading-bold">{title}</strong>}
			<div className="bwf-flex-wrapper">
				{settingOptions.map((option) => {
					if (option.type) {
						if ('toggle' === option.type) {
							return (
								<div
									className="bwf-small-input-wrapper"
									key={option.id}
								>
									<ToggleControl
										label={option.label}
										id={option.id}
										checked={typography[option.selectindex]}
										onChange={(value) => {
											const typographyall = [
												...typography,
											];
											typographyall[option.selectindex] =
												value;
											setTypography(typographyall);
										}}
									/>
								</div>
							);
						} else if ('color' === option.type) {
							return (
								<div
									className="bwf-small-input-wrapper"
									key={option.id}
								>
									&nbsp;
									<BwfColorPicker
										color={typography[option.selectindex]}
										onChange={(value) => {
											const typographyall = [
												...typography,
											];
											typographyall[option.selectindex] =
												value.hex;
											setTypography(typographyall);
										}}
									/>
								</div>
							);
						}
					} else {
						return (
							<div
								className="bwf-small-input-wrapper"
								key={option.id}
							>
								<Button className="bwf-heading-light">
									{option.label}
								</Button>
								<div className={'bwf-input bwf-' + option.id}>
									<label htmlFor={option.id}></label>
									<input
										type="number"
										value={typography[option.selectindex]}
										id={option.id}
										min="0"
										max="200"
										onChange={(event) => {
											const value = event.target.value;
											const typographyall = [
												...typography,
											];
											typographyall[option.selectindex] = value;
											setTypography(typographyall);
										}}
									/>
								</div>
							</div>
						);
					}
				})}
			</div>
		</div>
	);
};

export const BoxShadowComponent = (props) => {
	const { h_offset, v_offset, blur, spread, color, inset } = props.boxShadow;
	const typography = [h_offset, v_offset, blur, spread, color, inset];
	const defaultBoxShadow = {
		h_offset: '',
		v_offset: '',
		blur: '',
		spread: '',
		color: '',
		inset: '',
	};
	return (
		<>
			<FieldSettings
				typography={typography}
				setTypography={(boxshadow) => {
					const tempoxshadow = {
						h_offset: boxshadow[0],
						v_offset: boxshadow[1],
						blur: boxshadow[2],
						spread: boxshadow[3],
						color: boxshadow[4],
						inset: boxshadow[5],
					};
					props.onChange(tempoxshadow);
				}}
				settingOptions={shadowOptions}
			/>
			<Button className={"is-secondary is-small mt-10"} onClick={() => props.onChange(defaultBoxShadow)}>{__('Reset ')}{IconReset()}</Button>
		</>
	);
};

const TypographySettings = (props) => {
	const {
		font,
		text,
		lineHeight,
		letterSpacing,
		onChangeFont,
		onChangeText,
		onChangeLineHeight,
		onChangeLetterSpacing,
		disable = [],
	} = props;

	const [showAdvanceTypo, setAdvacenTypo] = useState(false);
	const toggleAdvaceTypo = () => setAdvacenTypo(!showAdvanceTypo);


	return (
		<>
			<br />
			<BasicTypography {...props} />
			{
				disable.includes('advance-typography') || (<>
					{onChangeText && (
						<TextSettings
							text={text}
							onChange={onChangeText}
							disable={disable}
							showAdvanceTypo={showAdvanceTypo}
							toggleAdvaceTypo={toggleAdvaceTypo}
						/>
					)}

					{showAdvanceTypo && onChangeLetterSpacing && (
						<AdvancedRangeControl
							label={__('Letter Spacing', 'funnel-builder-powerpack')}
							units={['px', 'em']}
							min={0}
							max={15}
							step={0.1}
							allowReset={true}
							value={letterSpacing && letterSpacing.value}
							unit={letterSpacing && letterSpacing.hasOwnProperty('unit') && letterSpacing.unit}
							onChange={(newVal) => {
								let Updatedvalue = cloneDeep(letterSpacing && typeof letterSpacing === 'object' ? letterSpacing : {});
								Updatedvalue.value = newVal;
								onChangeLetterSpacing(Updatedvalue);
							}}
							onChangeUnit={(newUnit) => {
								let Updatedvalue = cloneDeep(letterSpacing && typeof letterSpacing === 'object' ? letterSpacing : {});
								Updatedvalue.unit = newUnit;
								onChangeLetterSpacing(Updatedvalue);
							}}
						/>
					)}
				</>)
			}
		</>
	);
};

export default TypographySettings;

export const BasicTypography = (props) => {
	const {
		font,
		text,
		lineHeight,
		letterSpacing,
		onChangeFont,
		onChangeText,
		onChangeLineHeight,
		onChangeLetterSpacing,
		disable = [],
		fontStyle = false,
	} = props;
	const textValues = cloneDeep(text ? text : {});
	const { align } = textValues;

	const onAlignChange = (newVal) => {
		const value = { ...textValues, ...{ align: newVal } };
		onChangeText(cloneDeep(value));
	}
	return (<>
		{onChangeFont && (<>
			<FontSettings
				font={font}
				onChange={onChangeFont}
				disable={disable}
				fontStyle={fontStyle}
			/>
		</>)}
		{(!onChangeText || disable.includes('text-align')) || <>
			<AlignButtonsControl
				className={'bwf-align-typo'}
				value={align ? align : ''}
				onChange={onAlignChange}
			/>
			<CardDivider />
		</>}
		{(!onChangeLineHeight || disable.includes('line-height')) || (
			<>
				<AdvancedRangeControl
					label={__('Line Height', 'funnel-builder-powerpack')}
					units={['px', 'em']}
					min={0}
					max={250}
					step={1}
					allowReset={true}
					value={lineHeight && lineHeight.value}
					unit={lineHeight && lineHeight.hasOwnProperty('unit') && lineHeight.unit}
					onChange={(newVal) => {
						let Updatedvalue = cloneDeep(lineHeight && typeof lineHeight === 'object' ? lineHeight : {});
						Updatedvalue.value = newVal;
						onChangeLineHeight(Updatedvalue);
					}}
					onChangeUnit={(newUnit) => {
						let Updatedvalue = cloneDeep(lineHeight && typeof lineHeight === 'object' ? lineHeight : {});
						Updatedvalue.unit = newUnit;
						onChangeLineHeight(Updatedvalue);
					}}
				/>
			</>
		)}
	</>);
}
