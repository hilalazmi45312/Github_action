/**
 * WordPress Global Objects
 */

import { __ } from '@wordpress/i18n';
const { TabPanel, Dashicon } = wp.components;
import { InspectorControls } from '@wordpress/block-editor';

import { Fragment } from '@wordpress/element';
// eslint-disable-next-line import/no-extraneous-dependencies
import { applyFilters, doAction } from '@wordpress/hooks';
import BWFBlocksResposivator from './blocks-responsivator';

/**
 *
 * @param {Object} props
 *                       usage : 
 *                       <BWFBlocksInspectorControl>
                         {(tab) => {
                         <Component tab={tab} />
                         }}
                         </BWFBlocksInspectorControl>
 */
const BWFBlocksInspectorControl = (props) => {
	const { children } = props; // children must be a function.
	return (
		<InspectorControls>
			<TabPanel
				className="bwf-inspector-tabs"
				activeClass="active-tab"
				tabs={[
					{
						name: 'general',
						title: (
							<>
								<Dashicon icon="admin-settings" />
								<span>
									{__('General', 'bwf-gutenberg-block')}
								</span>
							</>
						),
						className: 'bwf-tab-buttons tab-general',
					},
					{
						name: 'style',
						title: (
							<>
								<Dashicon icon="admin-appearance" />
								{__('Style', 'bwf-gutenberg-block')}
							</>
						),
						className: 'bwf-tab-buttons tab-style',
					},
				]}
			>
				{(tab) => {
					/**
					 * @param {string} tab.name current tab key
					 * @param {Object} tab      current tab object
					 * @param          device
					 */
					if ('style' === tab.name) {
						return (
							<BWFBlocksResposivator
								{...props}
								tab={tab}
								children={children}
							/>
						);
					}
					return (
						<Fragment>
							{children(tab.name, tab)}
							{applyFilters('bwfBlocksInspector.general', <></>, {
								...props,
							})}
						</Fragment>
					);
				}}
			</TabPanel>
		</InspectorControls>
	);
};
export default BWFBlocksInspectorControl;
