/**
 * WordPress depedencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import {
	CardDivider,
	SelectControl,
	FontSizePicker,
} from '@wordpress/components';

import Select from 'react-select';
import lodash from 'lodash';

import { FontFamilyList, FontWeightList, includeFontInIframe } from '../../utils/utils';
import { AdvancedRangeControl } from 'BWFOP/components';
import './style.scss';

const bwfFonts =
	bwfop_funnels_data && bwfop_funnels_data.bwf_g_fonts
		? bwfop_funnels_data.bwf_g_fonts
		: {};

const FontSettings = (props) => {
	const { fontStyle } = props;
	const fontValues = lodash.cloneDeep(props.font);
	const { family, weight, size, sizeUnit, style } = fontValues;
	const { onChange, disable = [] } = props;
	const setAttributes = (val) => {
		const value = { ...fontValues, ...val };
		onChange(lodash.cloneDeep(value));
	};
	const typographySelectOptions = [].concat.apply(
		[],
		FontFamilyList.map((option) => option.options)
	);
	const prevFont = useRef();

	const activeFont =
		family && typographySelectOptions
			? typographySelectOptions.filter(({ value }) => value === family)
			: '';

	useEffect(() => {
		if (family && bwfFonts[family]) {
			const fontWeightVal = weight ? (weight === 'normal' ? 'regular' : weight) : '';
			WebFont.load({
				google: {
					families: [
						(family + ':' + fontWeightVal).replace(
							/\s/g,
							'+'
						),
					],
				},
			});
		}
	}, [activeFont]);
	const FontWeights = bwfFonts[activeFont && activeFont[0].value]
		? bwfFonts[activeFont[0].value].w.reduce(function (
			result,
			item,
			index
		) {
			result[index] = { name: item, label: item };
			return result;
		},
			[])
		: FontWeightList;

	return (
		<>
			{disable.includes('font-family') || (<>
				<label className="bwf-flex-wrapper bwf-space-btw mb-10">
					{__('Font Family', 'funnel-builder-powerpack')}
				</label>
				<Select
					options={FontFamilyList}
					value={activeFont}
					isMulti={false}
					maxMenuHeight={300}
					isClearable={true}
					placeholder={__('Select a font family', 'funnel-builder-powerpack')}
					className="bwf-inspector-controls__select-font-family"
					onChange={(val) => {
						if (null === val) {
							setAttributes({ family: '', weight: '' })
						} else {
							setAttributes({ family: val.value, weight: '' });
						}
						if (
							val &&
							val.google &&
							bwfFonts[val.value]
						) {
							const fontFamily = (val.value +
								':' +
								bwfFonts[val.value].w[0]
							).replace(/\s/g, '+')
							if(props.onGoogleFont){
								props.onGoogleFont(
									(
										'https://fonts.googleapis.com/css?family=' +
										val.value +
										':' +
										bwfFonts[val.value].w[0]
									).replace(/\s/g, '+')
								);
								WebFont.load({
									google: {
										families: [fontFamily],
									},
								});

							}
							includeFontInIframe([fontFamily], prevFont)
						} else {
							props.onGoogleFont && props.onGoogleFont('');
						}
					}}
				/>
				<CardDivider />

			</>)}
			{disable.includes('font-size') || (<>
				<div className="bwf-component--fontsize">
					<AdvancedRangeControl
						label={__('Font Size', 'funnel-builder-powerpack')}
						units={['px', 'em', 'rem']}
						min={0}
						max={200}
						step={1}
						allowReset={true}
						value={size && size.hasOwnProperty('value') ? size.value : (size ? parseFloat(size) : '')}
						unit={sizeUnit}
						onChange={(newVal) => {
							setAttributes({ size: newVal });
						}}
						onChangeUnit={(newUnit) => {
							setAttributes({ sizeUnit: newUnit });
						}}
					/>
					<CardDivider />
				</div>
			</>)}

			{disable.includes('font-weight') || (<>
				<SelectControl
					label={__('Weight', 'funnel-builder-powerpack')}
					options={[
						{
							value: '',
							label: __(
								'Not Set',
								'funnel-builder-powerpack'
							),
						},
						...FontWeights,
					]}
					onChange={(value) => {
						setAttributes({
							weight: value === 'regular' ? 'normal' : value,
						});
					}}
					value={weight === 'normal' ? 'regular' : weight}
				/>
				<CardDivider />
			</>)}
			{
				fontStyle && (
					<>
						<SelectControl
							label={__('Style')}
							value={style}
							options={[
								{ label: 'Default', value: '' },
								{ label: 'Normal', value: 'normal' },
								{ label: 'Italic', value: 'italic' },
								{ label: 'Oblique', value: 'oblique' },
							]}
							onChange={newVal => setAttributes({ style: newVal })}
						/>
						<CardDivider />
					</>
				)
			}
		</>
	);
};
export default FontSettings;
