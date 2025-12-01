/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * WordPress dependencies
 */
import { getBlockType, registerBlockType } from '@wordpress/blocks'
import { applyFilters } from '@wordpress/hooks'

const registerBlock = ( name, settings = {} ) => {
    const blockCategory = 'woofunnels' // common category for all blocks
	
    if ( getBlockType( name ) ) {
		return
	}

	const blockName = name.replace( /^\w+\//g, '' )
	const blockSettings = {
		...settings,
		category: blockCategory,
        icon: settings.icon || 'block-default',
        edit: settings.edit || noop,
        save: settings.save || noop,
	}


	// Allow modules to modify the block's attributes.
	blockSettings.attributes = applyFilters( `bwf.${ blockName }.attributes`, blockSettings.attributes )

	// blockSettings.edit = withMainClassname( name )( blockSettings.edit )
	// blockSettings.save = withMainClassname( name )( blockSettings.save )

	// Register the block.
	registerBlockType( name, applyFilters( `bwf.${ blockName }.settings`, blockSettings ) )

	return blockSettings
}

export default registerBlock
