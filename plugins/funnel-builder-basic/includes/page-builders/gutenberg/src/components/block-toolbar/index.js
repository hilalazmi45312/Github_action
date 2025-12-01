import { Toolbar, IconButton, ToolbarButton, Dropdown, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BWFBlockToolbarResposivator from 'BWFOP/inspector/block-toolbar-reposivator';
import { BasicTypography } from 'BWFOP/components/typography'
import BWFColorPalette from 'BWFOP/components/color';
import { getCurrentScreen } from 'BWFOP/utils/utils';
import { SVGIcon } from 'BWFOP/utils/icons';
import { BwfBackground } from 'BWFOP/components';
import './style.scss';
import {colors} from "BWFOP/components/background";

export const TypograpghyToolbar = (props) => {
    const { attributes:attrs, setAttributes, disable=[] } = props;
    return(
        <Toolbar>
            <Dropdown
                className="components-dropdown-menu components-toolbar"
                contentClassName="bwf-block-toolbar"
                position="top center"
                renderToggle={ ( { isOpen, onToggle } ) => (
                    <ToolbarButton
                        icon={ <SVGIcon icon="typo"/> }
                        label={ __( 'Typography Settings' ) }
                        onClick={ onToggle }
                        aria-expanded={ isOpen }
                    />
                ) }
                renderContent={ () => (
                    <>
                        <BWFBlockToolbarResposivator>
                            { (screen) => 
                                <BasicTypography
                                    font={ attrs.font && attrs.font[screen] }
                                    text={ attrs.text && attrs.text[screen] }
                                    lineHeight={ attrs.lineHeight && attrs.lineHeight[screen] }
                                    onChangeFont={(value) => setAttributes({ font: { ...attrs.font, [screen]: value } } ) }
                                    onChangeText={(value) => setAttributes({ text: { ...attrs.text, [screen]: value } } ) }
                                    onChangeLineHeight={(value) => setAttributes({ lineHeight: { ...attrs.lineHeight, [screen]: value } } ) }
                                    disable={ [ ...disable, ['advance-typograpghy'] ] }
                                />
                            }
                        </BWFBlockToolbarResposivator>
                    </>
                ) }
            />
        </Toolbar>
    )
}

export const ColorToolbar = (props) => {
    const { attributes:attrs, setAttributes, disable=[], label=__( 'Color' ), attrKey='color', onChange } = props;
    const screen = getCurrentScreen();

    return(<>
        <Toolbar>
            <Dropdown
                className="components-dropdown-menu components-toolbar"
                contentClassName="bwf-block-toolbar"
                position="top center"
                renderToggle={ ( { isOpen, onToggle } ) => (
                    <ToolbarButton
                        icon={
                            <span className="bwf-toolbar-textclr-indicate" style={{ color: attrs && attrs[attrKey][screen] ? attrs[attrKey][screen] :  '#000' }}>
                                <SVGIcon icon="typo" color={ attrs && attrs[attrKey][screen] ? attrs[attrKey][screen] :  '#000'} size={18} />
                            </span>
                        }
                        label={ label }
                        title={ label }
                        onClick={ onToggle }
                        aria-expanded={ isOpen }
                    >
                    </ToolbarButton>
                ) }
                renderContent={ () => (
                    <>
                        <BWFColorPalette
                            label={label}
                            className="bwf-toolbar-color-pallete"
                            value={ attrs[attrKey] && attrs[attrKey][screen] }
                            onChange={(value) => {
                                setAttributes({ [attrKey]: { ...attrs[attrKey], [screen]: value } } );
                                onChange(value);
                            }}
                            disableCustomColors={false}
                            colors={colors}
                            clearable={true}
                        />
                    </>
                ) }
            />
        </Toolbar>
    </>)
}
export const BackgroundToolbar = (props) => {
    const { attributes:attrs, setAttributes, disable=[], label=__( 'Background' ), attrKey='background' } = props;
    const screen = getCurrentScreen();

    return(<>
         <Toolbar>
            <Dropdown
                className="components-dropdown-menu components-toolbar"
                contentClassName="bwf-block-toolbar"
                position="top center"
                renderToggle={ ( { isOpen, onToggle } ) => (
                    <ToolbarButton
                        icon={ <SVGIcon icon="fill" /> }
                        label={ label }
                        title={ label }
                        onClick={ onToggle }
                        aria-expanded={ isOpen }
                    >
                        {/* <span className="component-color-indicator bwf-color-indicate" style={{ background: attrs[attrKey] && attrs[attrKey][screen]  }}></span> */}
                    </ToolbarButton>
                ) }
                renderContent={ () => (
                    <>
                        <BwfBackground
							background={attrs[attrKey] && attrs[attrKey][screen]}
							onChange={(value) => {
								const tempBackground = JSON.parse( JSON.stringify( attrs[attrKey] ? attrs[attrKey] : {} ) );
								tempBackground[screen] = value;
								setAttributes({ [attrKey]: tempBackground });
							}}
							disable={[...disable, ...['blend-mode', 'image-settings']]}
						/>
                        <div>Go to <i>Style {'>'} Background</i> for more settings.</div>
                    </>
                ) }
            />
        </Toolbar>
    </>)
}