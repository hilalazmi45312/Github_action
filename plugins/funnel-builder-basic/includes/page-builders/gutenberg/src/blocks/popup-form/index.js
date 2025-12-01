// eslint-disable-next-line import/no-extraneous-dependencies
import { page } from '@wordpress/icons';
import {attributes} from './attributes.js';
import edit from './edit';
import save from './save';
import './editor.scss';
import {SVGIcon} from "../../utils/icons";

export { default as metadata } from './block.json'
export const settings = {
	icon: <SVGIcon icon={"popup-form"}/>,
	supports: {
		anchor: true,
	},
	attributes,
	getEditWrapperProps(attr) {
		const { blockAlignment } = attr;
		if (
			'left' === blockAlignment ||
			'right' === blockAlignment ||
			'full' === blockAlignment
		) {
			return { 'data-align': blockAlignment };
		}
	},
	edit,
	save,

};
