import { Button, ButtonGroup } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

export const ButtonGroupComponent = (props) => (
	<div>
		<div className="bwf-flex-wrapper">
			<label className="bwf-label">
				{__('Button Size', 'bwf-gutenberg-block')}
			</label>
			<ButtonGroup>
				<Button isPrimary>{__('Auto', 'bwf-gutenberg-block')}</Button>
				<Button>{__('Fixed', 'bwf-gutenberg-block')} </Button>
				<Button>{__('Full WIdth', 'bwf-gutenberg-block')} </Button>
			</ButtonGroup>
		</div>
	</div>
);

export const ScreenButton = (props) => {
	return (
		<div className="bwf-screen-btn-wraps">
			<Button
				onClick={() => {
					const bodyWrapper = document.querySelector(
						'.edit-post-visual-editor'
					);
					if (bodyWrapper) {
						bodyWrapper.classList.remove('bwf-mobile-screen');
						bodyWrapper.classList.remove('bwf-tab-screen');
					}
					props.onChange && props.onChange('desktop');
				}}
			>
				<span className="dashicons dashicons-desktop"></span>
			</Button>
			<Button
				onClick={() => {
					const bodyWrapper = document.querySelector(
						'.edit-post-visual-editor'
					);
					if (bodyWrapper) {
						bodyWrapper.classList.remove('bwf-mobile-screen');
						if (bodyWrapper.classList.contains('bwf-tab-screen')) {
							bodyWrapper.classList.remove('bwf-tab-screen');
						} else {
							bodyWrapper.classList.add('bwf-tab-screen');
						}
					}
					props.onChange && props.onChange('tab');
				}}
			>
				<span className="dashicons dashicons-tablet"></span>
			</Button>
			<Button
				onClick={() => {
					const bodyWrapper = document.querySelector(
						'.edit-post-visual-editor'
					);
					if (bodyWrapper) {
						bodyWrapper.classList.remove('bwf-tab-screen');
						if (
							bodyWrapper.classList.contains('bwf-mobile-screen')
						) {
							bodyWrapper.classList.remove('bwf-mobile-screen');
						} else {
							bodyWrapper.classList.add('bwf-mobile-screen');
						}
					}
					props.onChange && props.onChange('mobile');
				}}
			>
				<span className="dashicons dashicons-smartphone"></span>
			</Button>
		</div>
	);
};

export const AddButtonComponent = (props) => {
	return (
		<Button className="is-primary" icon={plus}>
			{__('Add Button', 'bwf-gutenberg-block')}
		</Button>
	);
};

const StepButton = (props) => {
	const { label, defaultValue } = props;
	return (
		<div>
			<div className="bwf-flex-wrapper bwf-space-btw mb-10">
				<label>{__('Choose Screen', 'bwf-gutenberg-block')}</label>
				<ScreenButton {...props} />
			</div>
			<AddButtonComponent {...props} />
			<ButtonGroupComponent {...props} />
			<label className="bwf-label">{label}</label>
			<div className="bwf-flex-wrapper">
				<Button className="components-button bwf-step-btn is-secondary">
					<span className="dashicons dashicons-arrow-left"></span>
				</Button>
				<input
					type="number"
					className="bwf-step-input"
					value={defaultValue}
					onChange={() => {}}
				/>
				<Button className="components-button bwf-step-btn is-secondary">
					<span className="dashicons dashicons-arrow-right"></span>
				</Button>
			</div>
		</div>
	);
};

export default StepButton;
