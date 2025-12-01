/**
 * External dependencies
 */
import { noop } from 'lodash';
import { Button, Popover } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { IconList } from './icon-list';

const IconsPopover = (props) => {
	const [IconsUpdatedList, setIconsUpdatedList] = useState(IconList());
	const [visible, setVisible] = useState(false);

	const { onSelectIcon, removeSVG = noop } = props;

	// Open the upload dialog and let the user pick an SVG.
	const uploadSvg = (event) => {
		event.preventDefault();

		const input = document.createElement('input');
		input.accept = 'image/svg+xml';
		input.type = 'file';
		input.onchange = (e) => {
			const files = e.target.files;

			// Read the SVG,
			const fr = new FileReader();
			fr.onload = function (e) {
				const svgString = cleanSvgString(
					addCustomIconClass(e.target.result)
				);
				props.onSelectIcon(svgString);
				setVisible(false);
			};

			fr.readAsText(files[0]);
		};
		input.click();
	};
	return (
		<div>
			<Button
				className="components-button bwf-step-btn is-secondary"
				onClick={() => {
					setVisible(!visible);
				}}
			>
				{__('Set Icon', 'bwf-gutenberg-block')}
			</Button>
			{visible ? (
				<Popover
					position="right"
					onClose={() => {}}
					onFocusOutside={() => setVisible(false)}
				>
					<div className="pd-10 bwf-icons-popover">
						<input
							type="text"
							placeholder={__(
								'Search Icons',
								'bwf-gutenberg-block'
							)}
							onChange={(event) => {
								const Icons = IconList().filter((icon) =>
									icon.id.includes(event.target.value)
								);
								setIconsUpdatedList(Icons);
							}}
						/>
						<div className={'bwf-flex-wrapper'}>
							<Button isPrimary onClick={uploadSvg}>
								{__('Upload SVG')}
							</Button>
							<Button isSecondary onClick={removeSVG}>
								{__('Clear Icon')}
							</Button>
						</div>
						<br />
						<div className="bwf-small-grid">
							{IconsUpdatedList.map((icons) => (
								<Button
									key={icons.id}
									icon={icons.icon}
									onClick={() => onSelectIcon(icons)}
								></Button>
							))}
						</div>
					</div>
				</Popover>
			) : null}
		</div>
	);
};

export default IconsPopover;

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

/**
 * Adds a new class "bwf-custom-icon" to an SVG string.
 *
 * @param {string} svgString   The SVG in string form
 * @param {string} customClass Class to add
 * @return {string} The modified SVG
 */
export const addCustomIconClass = (svgString, customClass = 'bwf--icon') => {
	if (svgString.match(/(<svg[^>]*class=["'])/)) {
		// Svg with an existing class attribute.
		return svgString.replace(/(<svg[^>]*class=["'])/, `$1${customClass} `);
	} else if (svgString.match(/(<svg)/)) {
		// Svg without a class attribute.
		return svgString.replace(/(<svg)/, `$1 class="${customClass}"`);
	}
};
