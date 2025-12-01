/**
 * Internal dependencies
 */
import { searchFontAwesomeIconName } from './search';

/**
 * WordPress dependencies
 */
import {
	Popover,
	PanelBody,
	TextControl,
	Spinner,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * External dependencies
 */
import { faGetSVGIcon } from '../../utils/utils';
import classnames from 'classnames';
import FontAwesomeIcon from '../font-awesome-icon';

let searchTimeout = null;

/**
 * Adds a new class "bwf-custom-icon" to an SVG string.
 *
 * @param {string} svgString The SVG in string form
 * @param {string} customClass Class to add
 *
 * @return {string} The modified SVG
 */
export const addCustomIconClass = (
	svgString,
	customClass = 'bwf-custom-icon'
) => {
	if (svgString.match(/(<svg[^>]*class=["'])/)) {
		// Svg with an existing class attribute.
		return svgString.replace(/(<svg[^>]*class=["'])/, `$1${customClass} `);
	} else if (svgString.match(/(<svg)/)) {
		// Svg without a class attribute.
		return svgString.replace(/(<svg)/, `$1 class="${customClass}"`);
	}
	return svgString;
};

/**
 * Cleans up the SVG, removes the <?xml> tag and comments
 *
 * @param {string} svgString The SVG in string form
 */
export const cleanSvgString = (svgString) => {
	// Get the SVG only
	let newSvg = svgString
		.replace(/(^[\s\S]*?)(<svg)/gm, '$2')
		.replace(/(<\/svg>)([\s\S]*)/g, '$1');

	// Remove simple grouping so that we can color SVGs.
	for (let i = 0; i < 2; i++) {
		newSvg = newSvg.replace(/\s*<g\s*>([\s\S]*?)<\/g>\s*/gm, '$1');
	}

	return newSvg;
};

const IconSearchPopover = (props) => {
	const [value, setValue] = useState('');
	const [results, setResults] = useState([]);
	const [isBusy, setIsBusy] = useState(false);

	const allowSVGUpload = props.returnSVGValue;

	// Debounce search.
	useEffect(() => {
		let isMounted = true;
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(() => {
			if (!isMounted) {
				return;
			}
			setIsBusy(true);
			searchFontAwesomeIconName(value)
				.then((results) => {
					if (isMounted) {
						setResults(results);
					}
				})
				.finally(() => {
					if (isMounted) {
						setIsBusy(false);
					}
				});
		}, 500);

		return () => {
			isMounted = false;
			clearTimeout(searchTimeout);
		};
	}, [value]);


	// Open the upload dialog and let the user pick an SVG.
	const uploadSvg = (event) => {
		event.preventDefault();

		const input = document.createElement('input');
		input.accept = 'image/svg+xml';
		input.type = 'file';
		input.onchange = (e) => {
			const files = e.target.files;
			if (!files.length) {
				return;
			}

			// Read the SVG,
			const fr = new FileReader();
			fr.onload = function (e) {
				const svgString = cleanSvgString(
					addCustomIconClass(e.target.result)
				);
				props.onChange(svgString);
				props.onClose();
			};

			fr.readAsText(files[0]);
		};
		input.click();
	};

	const labelContainerClasses = classnames(
		['bwf-icon-popover__label-container'],
		{
			'bwf-icon--has-upload': allowSVGUpload,
			'bwf-icon--has-reset': props.allowReset,
		}
	);

	return (
		<Popover
			className="bwf-icon-popover"
			onClose={props.onClose}
			onClickOutside={props.onClickOutside}
			position={props.position}
			anchorRef={props.anchorRef}
		>
			<PanelBody scrollAfterOpen={false}>
				<div className={labelContainerClasses}>
					<TextControl
						className="bwf-icon-popover__input"
						value={value}
						onChange={setValue}
						placeholder={__('Type to search icon', 'funnel-builder-powerpack')}
					/>
					{allowSVGUpload && (
						<Button
							onClick={uploadSvg}
							isPrimary
							className="components-range-control__upload"
						>
							{__('Upload SVG', 'funnel-builder-powerpack')}
						</Button>
					)}
					{props.allowReset && (
						<Button
							onClick={() => {
								props.onChange('');
								props.onClose();
							}}
							isSecondary
							className="components-range-control__reset"
						>
							{__('Clear icon', 'funnel-builder-powerpack')}
						</Button>
					)}
				</div>
				<div className="bwf-icon-popover__iconlist">
					{isBusy && <Spinner />}
					{!isBusy &&
						results.map(({ prefix, iconName }, i) => {
							const iconValue = `${prefix}-${iconName}`;
							return (
								<button
									key={i}
									className={`components-button bwf-prefix--${prefix} bwf-icon--${iconName}`}
									onClick={() => {
										if (props.returnSVGValue) {
											props.onChange(
												cleanSvgString(
													faGetSVGIcon(
														prefix,
														iconName
													)
												)
											);
										} else {
											props.onChange(
												iconValue,
												prefix,
												iconName
											);
										}
										props.onClose();
									}}
								>
									<FontAwesomeIcon
										prefix={prefix}
										iconName={iconName}
									/>
								</button>
							);
						})}
					{!isBusy && !results.length && (
						<p className="components-base-control__help">
							{__('No matches found', 'funnel-builder-powerpack')}
						</p>
					)}
				</div>
			</PanelBody>
		</Popover>
	);
};

IconSearchPopover.defaultProps = {
	onChange: () => {},
	onClose: () => {},
	onClickOutside: () => {},
	returnSVGValue: true, // If true, the value provided in onChange will be the SVG markup of the icon. If false, the value will be a prefix-iconName value.
	allowReset: true,
	anchorRef: undefined,
	position: 'center',
};

export default IconSearchPopover;
