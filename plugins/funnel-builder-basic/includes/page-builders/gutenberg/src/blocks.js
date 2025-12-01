
import registerBlock from './utils/register-block';
import { merge } from 'lodash';
import 'regenerator-runtime/runtime.js';

import './utils/block-css.js';

// Import all index.js and register all the blocks found (if name & settings are exported by the script)
const importAllAndRegister = r => {
	r.keys().forEach( key => {
		const { metadata, settings } = r( key )
        const { slug, ...blockData } = metadata;
        const blockSettings = merge( blockData, settings );
		try {
			return slug && blockSettings && registerBlock( slug, blockSettings )
		} catch ( error ) {
			console.error( `Could not register ${ slug } block` ) // eslint-disable-line
		}
	} )
}

importAllAndRegister( require.context( './blocks', true, /index\.js$/ ) )
  