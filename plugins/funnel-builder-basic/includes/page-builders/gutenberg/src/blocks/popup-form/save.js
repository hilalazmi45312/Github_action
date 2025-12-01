/* eslint-disable react/jsx-no-target-blank */
/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

const Save = (props) => {

	return <InnerBlocks.Content className={props.className} />;
};

export default Save;
