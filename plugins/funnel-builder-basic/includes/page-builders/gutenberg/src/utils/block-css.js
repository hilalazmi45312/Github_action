import { createHigherOrderComponent } from "@wordpress/compose";
import { addFilter } from '@wordpress/hooks';

const BWFOPUniqueId = createHigherOrderComponent((BlockListBlock) => {
	return (props) => {
		const {
			name: blockName,
			attributes: { uniqueID },
		} = props;

		if (
			blockName.includes('bwfblocks')
		) {
			const wrapperProps = {
				...props.wrapperProps,
				'data-bwfblock-id': uniqueID,
			};
			return <BlockListBlock {...props} wrapperProps={wrapperProps} />;
		}
		return <BlockListBlock {...props} wrapperProps={props.wrapperProps} />;
	};
}, 'BWFOPUniqueId');
addFilter(
	'editor.BlockListBlock',
	'bwfopblock/editor/class/with-custom-classes',
	BWFOPUniqueId
);
