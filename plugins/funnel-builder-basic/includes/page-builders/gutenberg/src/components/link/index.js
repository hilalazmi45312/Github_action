import { Button, ToggleControl, SelectControl } from '@wordpress/components';
import { URLPopover } from '@wordpress/block-editor';
import { keyboardReturn, link } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
const { useState } = wp.element;

export const URLLinkSetting = (props) => {
	const { link, onChange, type } = props;
	const { urlLink, target, nofollow, attribute, title } = link;

	return (
		<div>
			<label htmlFor="bwf-url" className="bwf-label">
				{__('URL', 'bwf-gutenberg-block')}
			</label>
			<input
				style={{ width: '100%' }}
				type="url"
				value={urlLink}
				onChange={(urlLink) => {
					const tempLink = JSON.parse(JSON.stringify(link));
					tempLink.urlLink = urlLink.target.value;
					onChange(tempLink);
				}}
				id="bwf-url"
				placeholder={__('Enter URL', 'bwf-gutenberg-block')}
			/>
			<label htmlFor="bwf-url" className="bwf-label">
				{__('Title', 'bwf-gutenberg-block')}
			</label>
			<input
				style={{ width: '100%' }}
				type="text"
				value={title}
				onChange={(title) => {
					const tempLink = JSON.parse(JSON.stringify(link));
					tempLink.title = title.target.value;
					onChange(tempLink);
				}}
				id="bwf-url"
				placeholder={__('Enter Title', 'bwf-gutenberg-block')}
			/>
			<SelectControl
				label={__('Link Target', 'bwf-gutenberg-block')}
				value={target}
				style={{ width: '100%' }}
				options={[
					{
						label: __('Same Window', 'bwf-gutenberg-block'),
						value: 'self',
					},
					{
						label: __('New Window', 'bwf-gutenberg-block'),
						value: '_blank',
					},
				]}
				onChange={(target) => {
					const tempLink = JSON.parse(JSON.stringify(link));
					tempLink.target = target;
					onChange(tempLink);
				}}
			/>
			<ToggleControl
				label={__('Set Link to no Follow', 'bwf-gutenberg-block')}
				checked={nofollow}
				onChange={(nofollow) => {
					const tempLink = JSON.parse(JSON.stringify(link));
					tempLink.nofollow = nofollow;
					onChange(tempLink);
				}}
			/>
			<ToggleControl
				label={__(
					'Set link attribute Sponsored?',
					'bwf-gutenberg-block'
				)}
				checked={attribute}
				onChange={(attribute) => {
					const tempLink = JSON.parse(JSON.stringify(link));
					tempLink.attribute = attribute;
					onChange(tempLink);
				}}
			/>
		</div>
	);
};
export const URLLinkSettings = (props) => {
	const {
		attributes: { urlLink, target, nofollow, attribute },
		setAttributes,
	} = props;
	return (
		<div>
			<label htmlFor="bwf-url" className="bwf-label">
				{__('URL', 'bwf-gutenberg-block')}
			</label>
			<input
				style={{ width: '100%' }}
				type="url"
				value={urlLink}
				onChange={(urlLink) =>
					setAttributes({ urlLink: urlLink.target.value })
				}
				id="bwf-url"
				placeholder={__('Enter URL', 'bwf-gutenberg-block')}
			/>
			<SelectControl
				label={__('Link Target', 'bwf-gutenberg-block')}
				value={target}
				style={{ width: '100%' }}
				options={[
					{
						label: __('Same Window', 'bwf-gutenberg-block'),
						value: 'self',
					},
					{
						label: __('New Window', 'bwf-gutenberg-block'),
						value: '_blank',
					},
				]}
				onChange={(target) => {
					setAttributes({ target });
				}}
			/>
			<ToggleControl
				label={__('Set Link to no Follow', 'bwf-gutenberg-block')}
				checked={nofollow}
				onChange={(nofollow) => setAttributes({ nofollow })}
			/>
			<ToggleControl
				label={__(
					'Set link attribute Sponsored?',
					'bwf-gutenberg-block'
				)}
				checked={attribute}
				onChange={(attribute) => setAttributes({ attribute })}
			/>
		</div>
	);
};

const URLPopoverComponent = (props) => {
	const [isVisible, openURLPopover] = useState(false);
	const [url, onChangeURL] = useState('');
	const [opensInNewWindow, setopensInNewWindow] = useState(true);
	return (
		<div>
			<Button icon={link} onClick={() => openURLPopover(!isVisible)}>
				Edit URL
			</Button>
			{isVisible && (
				<URLPopover
					className="pd-10"
					onClose={() => openURLPopover(!isVisible)}
					renderSettings={() => (
						<ToggleControl
							label={__('Open in new tab', 'bwf-gutenberg-block')}
							checked={opensInNewWindow}
							onChange={() =>
								setopensInNewWindow(!opensInNewWindow)
							}
						/>
					)}
				>
					<form
						onSubmit={() => openURLPopover(!isVisible)}
						className="pd-10"
					>
						<input
							type="url"
							value={url}
							onChange={(url) => onChangeURL(url.target.value)}
						/>
						<Button
							icon={keyboardReturn}
							label={__('Apply', 'bwf-gutenberg-block')}
							type="submit"
						/>
					</form>
				</URLPopover>
			)}
		</div>
	);
};
export default URLPopoverComponent;
