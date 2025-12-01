/**
 * WordPress depedencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	CardDivider,
	ColorIndicator,
	Dashicon,
	SelectControl,
	ToggleControl,
	Toolbar,
	ToolbarButton,
	ToolbarGroup,
	Tooltip,
} from '@wordpress/components';
import { Fragment, useState } from '@wordpress/element';
import { alignLeft, alignRight, alignCenter } from '@wordpress/icons';

import classNames from 'classnames';
import lodash from 'lodash';

import { colors } from '../background';
import { FieldSettings } from '../typography';
import ColorPalette from 'BWFOP/components/color';
import {
	textAlignSetting,
	textDecorationSetting,
	textTransformSetting,
} from '../../utils/utils';

const shadowOptions = [
	{
		id: 'x',
		selectindex: 0,
		label: __('X', 'wp-demo-block'),
	},
	{
		id: 'y',
		selectindex: 1,
		label: __('Y', 'wp-demo-block'),
	},
	{
		id: 'blur',
		selectindex: 2,
		label: __('Blur', 'wp-demo-block'),
	},
	{
		id: 'color',
		selectindex: 3,
		label: __('Color', 'wp-demo-block'),
		type: 'color',
	},
];

const decorationStyle = [
	{
		label: __('Solid', 'bwf-gutenberg-block'),
		value: 'solid ',
	},
	{
		label: __('Dotted', 'bwf-gutenberg-block'),
		value: 'dotted',
	},
	{
		label: __('Double', 'bwf-gutenberg-block'),
		value: 'double',
	},
	{
		label: __('Dotted', 'bwf-gutenberg-block'),
		value: 'dotted',
	},
	{
		label: __('Wavy', 'bwf-gutenberg-block'),
		value: 'wavy ',
	},
	{
		label: __('Dashed', 'bwf-gutenberg-block'),
		value: 'dashed',
	},
	{
		label: __('Inherit', 'bwf-gutenberg-block'),
		value: 'inherit',
	},
];
export const TextSettings = (props) => {
	/** Exit is align false */
	if (false === align) {
		return null;
	}
	const textValues = lodash.cloneDeep(props.text);
	const { align, shadow, transform } = textValues;
	const { onChange, disable = [], showAdvanceTypo, toggleAdvaceTypo } = props;
	const setAttributes = (val) => {
		const value = { ...textValues, ...val };
		onChange(lodash.cloneDeep(value));
	};
	return (
		<>
			{/* {
				 <div style={ {display:'grid', gridGap: '10px', gridTemplateColumns: disable.includes('text-align') ? '1fr' : '1fr 1fr', marginTop: '15px'} }>
					 { disable.includes('text-align') || (
						 <SelectControl
							 label={ __( 'Alignment' ) }
							 value={ align }
							 options={ [
								 { label: 'Default', value: '' },
								 { label: 'Left', value: 'left' },
								 { label: 'Center', value: 'center' },
								 { label: 'Right', value: 'right' },
							 ] }
							 onChange={ newVal => setAttributes({ align: newVal }) }
						 />
					 ) }
				 </div>
			 } */}
			{/* { showAdvanceTypo disable.includes('text-transform') || ( */}
			<ToggleControl
				label={ __( 'Show Advanced Typography' ) }
				checked={ showAdvanceTypo }
				onChange={ toggleAdvaceTypo }
			/>
			{ disable.includes('text-transform') || showAdvanceTypo && (
				<SelectControl
					label={ __( 'Transform' ) }
					value={ transform }
					options={ [
						{ label: 'Default', value: '' },
						{ label: 'Capitalize', value: 'capitalize' },
						{ label: 'Uppercase', value: 'uppercase' },
						{ label: 'Lowercase', value: 'lowercase' },
					] }
					onChange={ newVal => setAttributes({ transform: newVal }) }
				/>
			) }
			{ disable.includes('text-decoration-line') || showAdvanceTypo && (
				<>
					<label htmlFor={'bwf-button-style'}>
						{__('Text Decoration Line', 'funnel-builder-powerpack')}
					</label>
					<div className={'bwf-responsive-tabs-list bwf-relative'} style={{padding:'10px 0'}}>
						{textDecorationSetting.map((TData) => {
							return (
								<Button
									key={TData.id}
									onClick={() => {
										setAttributes({
											'decoration-line': TData.id,
										});
									}}
									className={classNames('bwf-screen-tabs', {
										'active-screen':
											props.text['decoration-line'] &&
											props.text['decoration-line'] ===
											TData.id,
									})}
								>
									<Tooltip
										position="top center"
										text={TData.label}
									>
										 <span className={'bwf-device-brick'}>
											 <Dashicon icon={TData.icon} />
										 </span>
									</Tooltip>
								</Button>
							);
						})}
					</div>
					<CardDivider />
				</>
			)}
			{disable.includes('text-decoration-style') || showAdvanceTypo && (
				<>
					<SelectControl
						label={__('Text Decoration Style', 'funnel-builder-powerpack')}
						value={
							props.text['decoration-style']
								? props.text['decoration-style']
								: 'solid'
						}
						style={{ width: '100%' }}
						options={decorationStyle}
						onChange={(style) => {
							setAttributes({ 'decoration-style': style });
						}}
					/>
					<CardDivider />
				</>
			)}
			{disable.includes('text-decoration-color') || showAdvanceTypo && (
				<>
					<label className="bwf-label" htmlFor={'bwf-button-style'}>
						{__('Text Decoration Color', 'funnel-builder-powerpack')}
						<ColorIndicator
							className="bwf-color-indicator"
							colorValue={
								props.text['decoration-color']
									? props.text['decoration-color']
									: ''
							}
						/>
					</label>
					<ColorPalette
						value={
							props.text['decoration-color']
								? props.text['decoration-color']
								: ''
						}
						onChange={(newColor) => {
							setAttributes({ 'decoration-color': newColor });
						}}
						disableCustomColors={false}
						colors={colors}
						clearable={true}
					/>
					<CardDivider />
				</>
			)}

			{disable.includes('text-shadow') || showAdvanceTypo && (
				<>
					<label className="bwf-label" htmlFor={'bwf-button-style'}>
						{__('Text Shadow', 'funnel-builder-powerpack')}
					</label>
					<TextShadowSettings
						textShadow={
							shadow ? Object.values(shadow) : [0, 0, 0, '']
						}
						setTypography={(val) => {
							const shadowValues = {
								x: val[0],
								y: val[1],
								blur: val[2],
								color: val[3],
							};
							setAttributes({ shadow: shadowValues });
						}}
					/>
					<CardDivider />
				</>
			)}
		</>
	);
};

export const TextAlignmentComponent = (props) => {
	const { textAlignment, onChange, label } = props;
	return (
		<Fragment>
			<label className="bwf-label">{label}</label>
			<Toolbar
				label={__('Alignment', 'funnel-builder-powerpack')}
				className="bwf-algin-warpper"
			>
				<ToolbarGroup>
					<ToolbarButton
						icon={alignLeft}
						className={
							'left' === textAlignment
								? 'bwf-active-btn bwf-no-before'
								: ''
						}
						label={__('Align Text Left', 'funnel-builder-powerpack')}
						onClick={() => onChange('left')}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={alignCenter}
						className={
							'center' === textAlignment
								? 'bwf-active-btn bwf-no-before'
								: ''
						}
						label={__('Align Text Center', 'funnel-builder-powerpack')}
						onClick={() => onChange('center')}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={alignRight}
						className={
							'right' === textAlignment
								? 'bwf-active-btn bwf-no-before'
								: ''
						}
						label={__('Align Text Right', 'funnel-builder-powerpack')}
						onClick={() => onChange('right')}
					/>
				</ToolbarGroup>
			</Toolbar>
		</Fragment>
	);
};

const TextShadowSettings = (props) => {
	const { textShadow, setTypography } = props;
	const [colorPopup, showColorPopup] = useState(false);
	return (
		<FieldSettings
			{...{
				typography: textShadow,
				setTypography,
				settingOptions: shadowOptions,
				colorPopup,
				showColorPopup,
			}}
		/>
	);
};

export default TextShadowSettings;