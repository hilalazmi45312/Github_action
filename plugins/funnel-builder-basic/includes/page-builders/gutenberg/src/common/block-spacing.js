/**
 * External dependencies
 */
 import { cloneDeep, defaultsDeep, omit } from 'lodash';
 /**
  * WordPress dependencies
  */
 import { PanelBody } from '@wordpress/components';
 import { __ } from '@wordpress/i18n';
 /**
  * Internal dependencies
  */
 import { DimensionsControl } from 'BWFOP/components';
 
 
 const BWFBlockSpacing = (props) => {
     const { attributes: {margin, padding }, children } = props;
     const propsToPass = { ...omit( props, [ 'label', 'initialOpen' ] ) }
 
 
     return (
         <PanelBody scrollAfterOpen={false} title={props.label} initialOpen={props.initialOpen}>
            <DimensionsControl
                { ...propsToPass }
                label={ __( 'Margin', 'funnel-builder-powerpack' ) }
                type={ 'margin' }
                attrKey={ 'margin' }
                attrVal={ margin }
                defaultSync={false}
            />
            <br />
            <br />
            <DimensionsControl
                { ...propsToPass }
                label={ __( 'Padding', 'funnel-builder-powerpack' ) }
                attrKey={ 'padding' }
                attrVal={ padding }
                defaultSync={false}
            />
            { children }
         </PanelBody>
     )
 }
 BWFBlockSpacing.defaultProps = {
     label: __( 'Spacing', 'funnel-builder-powerpack' ),
     initialOpen: true
 }
 export default BWFBlockSpacing;
 