import classNames from 'classnames';
/**
 * WordPress Global Objects
 */

const { TabPanel, Dashicon, Tooltip, Button } = wp.components;
import { Fragment, useEffect, useState } from '@wordpress/element';
import { applyFilters, doAction } from '@wordpress/hooks';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 *
 * @param {Object} props
 */
const BWFBlockToolbarResposivator = (props) => {
	
	const {children } = props; // children must be a function.
	
	const deviceType = useSelect( ( select ) => {
		const {
			__experimentalGetPreviewDeviceType = null,
		} = select( 'core/edit-post' );
		return __experimentalGetPreviewDeviceType ? __experimentalGetPreviewDeviceType() : 'Desktop';
	}, [] );

    const [screen, setScreen] = useState(deviceType ?? 'desktop')

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

	return (
		<>
			<div className={'bwf-responsive-tabs-wrap'}>
				<div className={'bwf-responsive-tabs-list'}>
					{TABS.map((TData) => {
						return (
							<Button
								key={TData.key}
								onClick={() => {
									setScreen(TData.key)
								}}
								className={classNames('bwf-screen-tabs', {
									'active-screen': screen.toLowerCase() === TData.key,
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
				<div className="bwf-tab-content">{children(screen.toLowerCase())}</div>
			</div>
		</>
	);
};

export default BWFBlockToolbarResposivator;
