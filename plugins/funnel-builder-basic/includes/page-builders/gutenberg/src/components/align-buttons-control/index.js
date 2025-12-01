/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { AdvancedToolbarControl } from 'BWFOP/components';
import { omit } from 'lodash';
import classnames from 'classnames';

const ALIGN_OPTIONS = [
	{
		value: 'left',
		title: __('Align Left', 'funnel-builder-powerpack'),
		icon: 'editor-alignleft',
	},
	{
		value: 'center',
		title: __('Align Center', 'funnel-builder-powerpack'),
		icon: 'editor-aligncenter',
	},
	{
		value: 'right',
		title: __('Align Right', 'funnel-builder-powerpack'),
		icon: 'editor-alignright',
	},
	{
		value: 'justify',
		title: __('Justified', 'funnel-builder-powerpack'),
		icon: 'editor-justify',
	},
];

const AlignButtonsControl = (props) => {
	const { justified, className } = props;

	return (
		<AdvancedToolbarControl
			{...omit(props, ['justified'])}
			className={classnames([className, 'bwf-align-buttons-control'])}
			controls={ALIGN_OPTIONS.filter((option) => {
				return !justified ? option.value !== 'justify' : true;
			})}
		/>
	);
};

AlignButtonsControl.defaultProps = {
	className: '',
	label: __('Align', 'funnel-builder-powerpack'),
	value: ALIGN_OPTIONS[0].value,
	justified: false,
};

export default AlignButtonsControl;
