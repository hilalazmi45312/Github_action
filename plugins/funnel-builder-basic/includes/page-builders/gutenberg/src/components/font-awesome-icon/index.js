import SVGSpinner from './icons/spinner.svg';
import {
	faAPILoaded,
	faIsAPILoaded,
	faGetSVGIcon,
	faIconLoaded,
} from '../../utils/utils';

import { RawHTML, useEffect, useState } from '@wordpress/element';
import { pick } from 'lodash';

const Spinner = () => {
	return null;
};

const FontAwesomeIcon = (props) => {
	const [forceUpdateCount, setForceUpdateCount] = useState(0);
	const forceUpdate = () => {
		setForceUpdateCount(forceUpdateCount + 1);
	};

	// Wait for the FA API to load.
	useEffect(() => {
		faAPILoaded().then(forceUpdate);
	}, []);

	const propsToPass = pick(props, ['className', 'color', 'fill', 'style']);

	// If given an svg, just display it.
	if (typeof props.value === 'string') {
		if (props.value.match(/^<svg/)) {
			return <span {...propsToPass} dangerouslySetInnerHTML={ { __html: props.value } } />;
		}
	}

	// There's a chance that the Font Awesome library hasn't loaded yet, wait for it.
	if (!faIsAPILoaded()) {
		return <Spinner />;
	}

	const prefix = props.value ? props.value.replace(/-.*$/, '') : props.prefix;
	const iconName = props.value
		? props.value.replace(/^.*?-/, '')
		: props.iconName;

	// Display the icon.
	if (prefix && iconName) {
		const iconHTML = faGetSVGIcon(prefix, iconName);

		// Just in case the icon hasn't loaded yet, wait for it.
		if (!iconHTML) {
			faIconLoaded(prefix, iconName).then(forceUpdate);
			return <Spinner />;
		}

		return <span {...propsToPass} dangerouslySetInnerHTML={ { __html: iconHTML } } />;
	}

	// If no value, just display a smiley placeholder.
	const iconHTML = faGetSVGIcon('far', 'smile');
	return (
		<span {...propsToPass} className={`${props.className} bwf-icon--faded`} dangerouslySetInnerHTML={ { __html: iconHTML } } />
	);
};

FontAwesomeIcon.Content = (props) => {
	const propsToPass = pick(props, ['className', 'color', 'fill', 'style']);

	// If given an svg, just display it.
	if (typeof props.value === 'string') {
		if (props.value.match(/^<svg/)) {
			return <span {...propsToPass} dangerouslySetInnerHTML={ { __html: props.value } } />;
		}
	}

	const prefix = props.value ? props.value.replace(/-.*$/, '') : props.prefix;
	const iconName = props.value
		? props.value.replace(/^.*?-/, '')
		: props.iconName;

	const iconHTML = faGetSVGIcon(prefix, iconName);
	return <span {...propsToPass} dangerouslySetInnerHTML={ { __html: iconHTML } } />;
};

FontAwesomeIcon.defaultProps = {
	prefix: '',
	iconName: '',
	value: '', // This is the old-style of prefix + iconName e.g. 'fab-apple'
};

export default FontAwesomeIcon;
