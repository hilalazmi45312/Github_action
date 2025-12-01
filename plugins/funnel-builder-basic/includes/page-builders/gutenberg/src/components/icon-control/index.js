/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BaseControl, Button } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element'

/**
 * External dependencies
 */
import { omit } from 'lodash';
import SVGIconControl from './icons/list.svg';
import IconSearchPopover from '../icon-search-popover';
import SvgIcon from '../svg-icon';

const IconControl = (props) => {
	const [ openPopover, setOpenPopover ] = useState( false )
	const [ clickedOnButton, setClickedOnButton ] = useState( false )
	const instanceId = useInstanceId( IconControl, 'iconControl' )

	return (
		<BaseControl
			className={`bwf-icon-control bwf-icon-control-${instanceId}`}
			{...omit(props, ['onChange', 'value'])}
		>
			<div className="bwf-icon-control__wrapper">
				<div className="bwf-icon-control__button-wrapper">
					<Button
						isSecondary
						className="bwf-icon-control__icon-button"
						onClick={() => {
							if (!clickedOnButton) {
								setOpenPopover( true )
							} else {
								// If the popup closed because this button was clicked (while the popup was open) ensure the popup is closed.
								// This is needed or else the popup will always open when spam clicking the button.
								setOpenPopover( false );
								setClickedOnButton( false );
							}
						}}
					>
						{props.value && <SvgIcon value={props.value} />}
						{!props.value &&
							<SvgIcon value={SVGIconControl} style={{ opacity: 0.3 }} />
						}
					</Button>
					{openPopover && (
						<IconSearchPopover
							onClickOutside={(event) => {
								// This statement checks whether the close was triggered by clicking on the button that opens this.
								// This is needed or else the popup will always open when spam clicking the button.
								if (event.target) {
									if (
										event.target.closest(
											`.bwf-icon-control-${instanceId}`
										)
									) {
										setClickedOnButton( true )
										return;
									}
								}
								setOpenPopover( false );
								setClickedOnButton( false );
							}}
							onClose={() => setOpenPopover( false ) }
							returnSVGValue={props.returnSVGValue}
							onChange={props.onChange}
						/>
					)}
				</div>
				<Button
					onClick={() => {
						if (props.onReset) {
							props.onReset();
						} else {
							props.onChange('');
						}
						setOpenPopover( false );
					}}
					isSmall
					isSecondary
					className="components-range-control__reset"
				>
					{__('Reset', 'funnel-builder-powerpack')}
				</Button>
			</div>
		</BaseControl>
	);
};

IconControl.defaultProps = {
	label: __('Icon', 'funnel-builder-powerpack'),
	value: '',
	returnSVGValue: true, // If true, the value provided in onChange will be the SVG markup of the icon. If false, the value will be a prefix-iconName value.
	onChange: () => {},
};

export default IconControl;
 