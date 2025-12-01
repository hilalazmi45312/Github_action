/**
 * External dependencies
 */
import { noop } from 'lodash';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n'
import { Button } from '@wordpress/components'
import { useDispatch, useSelect } from '@wordpress/data';
import { capitalizeFirstLetter, getCurrentScreen } from 'BWFOP/utils';
import './editor.scss';

const BWFResponsiveToggle = (props) => {
	const {children} = props;
	const screen = getCurrentScreen();
	const {
		__experimentalSetPreviewDeviceType = null,
	} = useDispatch( 'core/edit-post' );
	
	const customSetPreviewDeviceType = ( device ) => {
		if ( wp.data.select( 'core/edit-post' ) ) {
			__experimentalSetPreviewDeviceType( capitalizeFirstLetter( device ) );
		}
	};
	
	return(
		<div className="bwf-responsive-field">
			{ children }
			<div className="bwf-responsive-toggle">
				{
					['desktop', 'tablet', 'mobile'].map( (device) =>
						<Button
							key={device}
							className={ classNames( 'bwf-responsive-toggle-icon', { 'is-active': screen === device } ) }
							onClick={ () => customSetPreviewDeviceType(device) }
							icon={ 'mobile' === device ? 'smartphone' : device }
							showTooltip={ false }
							title={ capitalizeFirstLetter(device) }
						/>
					)
				}
			</div>
		</div>
	)
}

export default BWFResponsiveToggle;
