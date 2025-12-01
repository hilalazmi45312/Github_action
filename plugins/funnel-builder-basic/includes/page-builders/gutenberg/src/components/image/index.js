/* eslint-disable no-shadow */
import {
	Button,
	ButtonGroup,
	CardDivider,
	FocalPointPicker,
} from '@wordpress/components';
import ImageUpload from '../media';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const ImageFocalPoint = (props) => {
	const url = props.url;
	const [focalPoint, setFocalPoint] = useState({
		x: props.bgImagePostion[0] ? props.bgImagePostion[0] : 0,
		y: props.bgImagePostion[1] ? props.bgImagePostion[1] : 0,
	});

	const dimensions = {
		width: 400,
		height: 100,
	};

	/* Example function to render the CSS styles based on Focal Point Picker value */
	const style = {
		backgroundImage: `url(${url})`,
		backgroundPosition: `${focalPoint.x * 100}% ${focalPoint.y * 100}%`,
	};

	return (
		<>
			<FocalPointPicker
				url={url}
				dimensions={dimensions}
				value={focalPoint}
				onChange={(focalPoint) => {
					setFocalPoint(focalPoint);
					props.onChange([focalPoint.x, focalPoint.y]);
				}}
			/>
			<div style={style} />
		</>
	);
};

const imageSizes = [
	{
		name: 'cover',
		title: __('Cover', 'bwf-gutenberg-block'),
		className: 'is-secondary',
	},
	{
		name: 'contain',
		title: __('Contain', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
	{
		name: 'auto',
		title: __('Auto', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
];

const imageRepeats = [
	{
		name: 'no-repeat',
		title: __('No Repeat', 'bwf-gutenberg-block'),
		className: 'is-secondary',
	},
	{
		name: 'repeat',
		title: __('Repeat', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
	{
		name: 'repeat-x',
		title: __('Repeat X', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
	{
		name: 'repeat-y',
		title: __('Repeat Y', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
	{
		name: 'auto',
		title: __('Auto', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
];

const imageAttachments = [
	{
		name: 'scroll',
		title: __('Scroll', 'bwf-gutenberg-block'),
		className: 'is-secondary',
	},
	{
		name: 'fixed',
		title: __('Fixed', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
	{
		name: 'parallax',
		title: __('Parallax', 'bwf-gutenberg-block'),
		className: ' is-secondary',
	},
];

const ImageSettingsComponent = (props) => {
	const { onChange } = props;
	const imageValues = JSON.parse(JSON.stringify(props));
	const {
		bgImageUrl,
		bgImageSize,
		bgImageRepeat,
		bgImageAttachment,
		bgImagePostion,
	} = imageValues;

	const setAttributes = (val) => {
		const value = { ...imageValues, ...val };
		onChange(JSON.parse(JSON.stringify(value)));
	};
	return (
		<>
			<ImageUpload
				image={bgImageUrl}
				onChange={(newImage) => {
					setAttributes({
						bgImageUrl: newImage,
					});
				}}
			/>
			{bgImageUrl && (
				<>
					<ImageFocalPoint
						url={bgImageUrl.url}
						bgImagePostion={
							bgImagePostion ? bgImagePostion : [0, 0]
						}
						onChange={(bgImagePostion) =>
							setAttributes({ bgImagePostion })
						}
					/>
					<label htmlFor={'bwf-button-style'}>
						{__('Background Image Size', 'bwf-gutenberg-block')}
					</label>
					<br />
					<ButtonGroup className={'bwf-button-group'}>
						{imageSizes.map((size) => (
							<Button
								key={size.name}
								isPrimary={
									bgImageSize === size.name ? true : false
								}
								onClick={() => {
									setAttributes({
										bgImageSize: size.name,
									});
								}}
							>
								{size.title}
							</Button>
						))}
					</ButtonGroup>
					<br />
					{bgImageSize &&
						('contain' === bgImageSize ||
						'auto' === bgImageSize) && (
							<>
							<CardDivider />
								<label htmlFor={'bwf-button-style'}>
									{__(
										'Background Image Repeat',
										'bwf-gutenberg-block'
									)}
								</label>
								<br />
								<ButtonGroup className={'bwf-button-group'}>
									{imageRepeats.map((repeat) => (
										<Button
											key={repeat.name}
											isPrimary={
												bgImageRepeat === repeat.name
													? true
													: false
											}
											onClick={() => {
												setAttributes({
													bgImageRepeat: repeat.name,
												});
											}}
										>
											{repeat.title}
										</Button>
									))}
								</ButtonGroup>
								<br />
							</>
						)}
					{bgImageSize && (
						<>
						<CardDivider />
							<label htmlFor={'bwf-button-style'}>
								{__(
									'Background Image Attachment',
									'bwf-gutenberg-block'
								)}
							</label>
							<br />
							<ButtonGroup className={'bwf-button-group'}>
								{imageAttachments.map((attachment) => (
									<Button
										key={attachment.name}
										isPrimary={
											bgImageAttachment ===
											attachment.name
												? true
												: false
										}
										onClick={() => {
											setAttributes({
												bgImageAttachment:
													attachment.name,
											});
										}}
									>
										{attachment.title}
									</Button>
								))}
							</ButtonGroup>
						</>
					)}
				</>
			)}
		</>
	);
};

export default ImageSettingsComponent;
