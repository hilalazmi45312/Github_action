/**
 * WordPress dependencies
 */
import { Popover, Button } from '@wordpress/components'
import { __experimentalLinkControl as LinkControl } from '@wordpress/block-editor'
import { keyboardReturn } from '@wordpress/icons'
import { __ } from '@wordpress/i18n'

/**
 * External dependencies
 */
import classnames from 'classnames'
import {
	omit, omitBy, keys,
} from 'lodash'

const urlLabels = [
	{
		id: 'opensInNewTab',
		title: __( 'Opens in new tab', 'funnel-builder-powerpack' ),
	},
	{
		id: 'noFollowLink',
		title: __( 'Nofollow link', 'funnel-builder-powerpack' ),
	},
]

const UrlInputPopover = props => {
	const {
		value,
		newTab,
		noFollow,
	} = props

	const urlOptions = {
		url: value,
		opensInNewTab: newTab,
		noFollowLink: noFollow,
	}

	if ( ! props.onChange && ! props.onChangeNewTab && ! props.onChangeNoFollow ) {
		return null
	}

	const mainClassName = classnames( [
		'bwf-url-input-popover',
	] )

	const i18nUrlOptions = urlLabels.filter( ( { id } ) => {
		// Filters the options based on existing onChange props
		switch ( id ) {
			case 'opensInNewTab': return props.onChangeNewTab
			case 'noFollowLink': return props.onChangeNoFollow
			default: return true
		}
	} )

	return (
		<Popover
			className={ mainClassName }
			focusOnMount={ false }
			position={ props.position }
		>
			<LinkControl
				value={ urlOptions }
				settings={ i18nUrlOptions }
				showSuggestions={ ! props.disableSuggestions }
				onChange={
					option => {
						const onChangeKeys = {
							url: props.onChange,
							opensInNewTab: props.onChangeNewTab,
							noFollowLink: props.onChangeNoFollow,
						}

						// Gets only the changed values to update
						const changedValues = omitBy( omit( option, ['id', 'title', 'type', 'kind'] ), ( value, key ) => {
							return urlOptions[ key ] === value
						} )

						// Trigger onChange only to changed values
						keys( changedValues ).map( value => (
							onChangeKeys[ value ]( changedValues[ value ] )
						) )
					}
				}
			>
				<form>
					<input placeholder={ __( 'Search or type url', 'funnel-builder-powerpack' ) } value={ value } />
					<Button
						icon={ keyboardReturn }
						type="submit"
					/>
				</form>
			</LinkControl>
		</Popover>
	)
}

UrlInputPopover.defaultProps = {
	value: '',
	disableSuggestions: false,
	onChange: null,
	position: 'bottom center',
	newTab: false,
	noFollow: false,
	onChangeNewTab: null,
	onChangeNoFollow: null,
}

export default UrlInputPopover
