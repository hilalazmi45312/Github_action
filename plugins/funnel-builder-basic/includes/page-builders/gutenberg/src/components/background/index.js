/**
 * External dependencies
 */
 import semver from 'semver';

/**
 * Wordpress Dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	CardDivider,
	ColorIndicator,
	SelectControl,
	ButtonGroup,
	Button,
} from '@wordpress/components';

import * as WPComponents from "@wordpress/components";
import ImageSettingsComponent from '../image';
import { defaultColors, WP_VERSION } from 'BWFOP/utils/utils';
import BWFColorPalette from '../color';
import './style.scss'


export const colors = defaultColors;

export const gradientColors = [
	{
		name: __('Vivid cyan blue to vivid purple', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)',
		slug: 'vivid-cyan-blue-to-vivid-purple',
	},
	{
		name: __('Light green cyan to vivid green cyan', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%)',
		slug: 'light-green-cyan-to-vivid-green-cyan',
	},
	{
		name: __(
			'Luminous vivid amber to luminous vivid orange',
			'bwf-gutenberg-block'
		),
		gradient:
			'linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%)',
		slug: 'luminous-vivid-amber-to-luminous-vivid-orange',
	},
	{
		name: __('Luminous vivid orange to vivid red', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%)',
		slug: 'luminous-vivid-orange-to-vivid-red',
	},
	{
		name: __('Very light gray to cyan bluish gray', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%)',
		slug: 'very-light-gray-to-cyan-bluish-gray',
	},
	{
		name: __('Cool to warm spectrum', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%)',
		slug: 'cool-to-warm-spectrum',
	},
	{
		name: __('Blush light purple', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%)',
		slug: 'blush-light-purple',
	},
	{
		name: __('Blush bordeaux', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%)',
		slug: 'blush-bordeaux',
	},
	{
		name: __('Luminous dusk', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%)',
		slug: 'luminous-dusk',
	},
	{
		name: __('Pale ocean', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%)',
		slug: 'pale-ocean',
	},
	{
		name: __('Electric grass', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%)',
		slug: 'electric-grass',
	},
	{
		name: __('Midnight', 'bwf-gutenberg-block'),
		gradient:
			'linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%)',
		slug: 'midnight',
	},
];

const BwfBackground = (props) => {
	const { onChange, labelPrefix, labelSuffix, disable=[], bgDefaultType = null, setBgDefaultType, label = null, backgroundLabel = 'Background' } = props;
	let bgType, setBGType;
	if ( ! setBgDefaultType ) {
		[ bgType, setBGType ] = useState( 'Solid' );
	} else {
		bgType = bgDefaultType;
		setBGType = setBgDefaultType;
	}
	const bgValues = JSON.parse(JSON.stringify(props.background || {}));
	const setAttributes = (val) => {
		const value = { ...bgValues, ...val };
		onChange(JSON.parse(JSON.stringify(value)));
	};
	let bgTabs = [ 'Solid', 'Gradient' ];

	if( ! disable.includes( 'image' ) ) {
	bgTabs.push( 'Image' );
	}

	const checkWPCompatibility = () => {
		let compVersion = semver.valid( WP_VERSION ) ? WP_VERSION : semver.valid( semver.coerce( WP_VERSION ) );
		return semver.gte( compVersion, '5.9.0' );
	}
	return (
		<Fragment>
			{ !setBgDefaultType ? <ButtonGroup className={'bwf-button-group bwf-background-toggle'}>
				{
				bgTabs.map( type =>
					<Button
						variant={ bgType === type ? 'primary' : '' }
						onClick={ () => setBGType( type ) }
						isSmall
						key={type}
					>
						{ type }
					</Button>
				)
				}
			</ButtonGroup> : null }
			{
				'Solid' === bgType && (
					<BWFColorPalette
						label={
							label ? label : ( labelPrefix ? `${labelPrefix} ` : '' ) +
							backgroundLabel + __(' Color', 'funnel-builder-powerpack')
							+ ( labelSuffix ? `${labelSuffix} ` : '' )
						}
						value={bgValues.color}
						onChange={(newColor) => {
							// setAttributes({ gradient: '' });
							setAttributes({ color: newColor, gradient: null });
						}}
						disableCustomColors={false}
						colors={colors}
						clearable={true}
					/>
				)
			}

			{
				'Gradient' === bgType && (
					<>
						<label htmlFor="bwf-button-style" className="bwf-label">
							{label ? __(' Gradient', 'funnel-builder-powerpack') : (labelPrefix ? `${labelPrefix} ` : '') +
								( backgroundLabel +  __(' Gradient', 'funnel-builder-powerpack') ) +
								(labelSuffix ? `${labelSuffix} ` : '' ) }
							<ColorIndicator
								className="bwf-color-indicator"
								colorValue={bgValues.gradient}
							/>
						</label>
						{
							checkWPCompatibility() ?
								<WPComponents.GradientPicker
									label={__('Color')}
									value={bgValues.gradient}
									gradients={gradientColors}
									onChange={(value) => {
										setAttributes({ gradient: value });
									}}
									disableCustomGradients={false}
								/> :
								<WPComponents.__experimentalGradientPicker
									label={__('Color')}
									value={bgValues.gradient}
									gradients={gradientColors}
									onChange={(value) => {
										setAttributes({ gradient: value });
									}}
									disableCustomGradients={false}
								/>

						}
					</>
				)
			}


			{
				'Image' === bgType && (
					<>
						<label htmlFor="bwf-button-style" className="bwf-label">
							{label ? __(' Image', 'funnel-builder-powerpack')  : (labelPrefix ? `${labelPrefix} ` : '') +
								( backgroundLabel +  __(' Image', 'funnel-builder-powerpack') ) +
								(labelSuffix ? `${labelSuffix} ` : '' ) }
						</label>
						<ImageSettingsComponent
							onChange={(val) => {
								setAttributes({
									image: val.bgImageUrl,
									size: val.bgImageSize,
									repeat: val.bgImageRepeat,
									attachment: val.bgImageAttachment,
									position: val.bgImagePostion,
								});
							}}
							bgImageUrl={bgValues.image}
							bgImageSize={bgValues.size}
							bgImageRepeat={bgValues.repeat}
							bgImageAttachment={bgValues.attachment}
							bgImagePostion={bgValues.position}
							disable={disable}
						/>
					</>
				)
			}

		</Fragment>
	);
};


export default BwfBackground;
