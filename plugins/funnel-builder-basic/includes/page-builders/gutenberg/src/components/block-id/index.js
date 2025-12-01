/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl } from '@wordpress/components';

const BWFBlockID = (props) => {
	const {
		attributes: { blockID },
		setAttributes,
		blockLabel = __('Add ID'),
	} = props;

	return (
		<PanelBody scrollAfterOpen={false} title={blockLabel} initialOpen={false}>
			<TextControl
				label={blockLabel}
				value={blockID}
				onChange={(newID) => setAttributes({ blockID: newID })}
				help={__(
					'Please make sure the ID is unique and not used elsewhere on the page this form is displayed. This field allows A-z 0-9 & underscore chars without spaces.'
				)}
			/>
		</PanelBody>
	);
};

export default BWFBlockID;
