import classNames from 'classnames';
/**
 * WordPress Global Objects
 */

const { TabPanel, Dashicon, Tooltip, Button } = wp.components;
import { Fragment, useEffect, useState } from '@wordpress/element';
import { applyFilters, doAction } from '@wordpress/hooks';
import { useDispatch, useSelect } from '@wordpress/data';

import { capitalizeFirstLetter, getCurrentScreen } from 'BWFOP/utils/utils';

/**
 *
 * @param {Object} props
 */
const BWFBlocksResposivator = (props) => {
	
	const {children, tab, setAttributes } = props; // children must be a function.
	
	const deviceType = useSelect( ( select ) => {
		const {
			__experimentalGetPreviewDeviceType = null,
		} = select( 'core/edit-post' );
		return __experimentalGetPreviewDeviceType ? __experimentalGetPreviewDeviceType() : 'Desktop';
	}, [] );

	const {
		__experimentalSetPreviewDeviceType = null,
	} = useDispatch( 'core/edit-post' );
	
	const customSetPreviewDeviceType = ( device ) => {
		if ( wp.data.select( 'core/edit-post' ) ) {
			__experimentalSetPreviewDeviceType( capitalizeFirstLetter( device ) );
		}
	};
	
	useEffect(() => {
		const coreAdvancedBlock = document.querySelector(
			'.block-editor-block-inspector__advanced'
		);
		if (null !== coreAdvancedBlock) {
			coreAdvancedBlock.style.display = 'none';
		}
		return () => {
			if (null !== coreAdvancedBlock) {
				coreAdvancedBlock.style.display = 'block';
			}
		};

	}, []);

	const TABS = [
		{
			key: 'desktop',
			title: 'Desktop Screen',
		},
		{
			key: 'tablet',
			title: 'Tablet Screen',
		},
		{
			key: 'mobile',
			title: 'Mobile Screen',
		},
	];

	const tabContent = () => {
		const data = { device: deviceType.toLowerCase(), ...tab };
		return (
			<Fragment>
				{children(tab.name, data)}
				{applyFilters('bwfBlocksInspector.style', <></>, {
					...props,
				})}
			</Fragment>
		);
	};

	return (
		<>
			<div className={'bwf-responsive-tabs-wrap'}>
				<div className={'bwf-responsive-tabs-list'}>
					{TABS.map((TData) => {
						return (
							<Button
								key={TData.key}
								onClick={() => {
									customSetPreviewDeviceType(TData.key);
									setAttributes({'screen':TData.key})
								}}
								className={classNames('bwf-screen-tabs', {
									'active-screen': deviceType === capitalizeFirstLetter( TData.key ),
								})}
							>
								<Tooltip
									position="top center"
									text={TData.title}
								>
									<span className={'bwf-device-brick'}>
										<Dashicon icon={'mobile' === TData.key ? 'smartphone' : TData.key} />
									</span>
								</Tooltip>
							</Button>
						);
					})}
				</div>
				<div className="bwf-tab-content">{tabContent()}</div>
			</div>
		</>
	);
};

export default BWFBlocksResposivator;
