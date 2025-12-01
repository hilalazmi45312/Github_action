/**
 * Wordpress Dependencies
 */
import { Button, CardDivider, ColorIndicator, Dashicon, FontSizePicker, PanelBody, RangeControl, Tooltip } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { Fragment, useEffect, useState } from "@wordpress/element";

/**
 * Internal Dependeies
 */
import ColorPalette from "BWFOP/components/color";
import { defaultColors, PanelTitle, hoverTab, getCurrentScreen } from "./utils";
import DimensionsControl from "BWFOP/components/dimensional-control";
import { BWFBlockSizing } from "BWFOP/common";
import TypographySettings from "BWFOP/components/typography";
import BwfBackground from "BWFOP/components/background";
import BorderComponenet from "BWFOP/components/border";
import { BoxShadowComponent } from 'BWFOP/components/typography';
import { AdvancedRangeControl } from 'BWFOP/components';

/**
 * External Dependencies
 */
import lodash, { cloneDeep, omit, pickBy } from "lodash";
import classNames from "classnames";

const defaultBorderVal = {
    top: '',
    right: '',
    bottom: '',
    left: '',
    color: '',
    color_right: '',
    color_bottom: '',
    color_left: '',
    style: '',
    radius: '',
};

const defaultBoxShadowval = {
    h_offset: '',
    v_offset: '',
    blur: '',
    spread: '',
    color: '',
    inset: '',
}

/**
 * 
 * @param {*} selectedKey 
 * @param {*} value 
 * @param {*} props 
 */
const setAttributeVal = (selectedKey, value, props, screen = null) => {
    const { attributes: { [selectedKey]: data }, setAttributes } = props;
    if (!screen) {
        screen = getCurrentScreen();
    }
    let perviousVal = JSON.parse(JSON.stringify(props.attributes[selectedKey] ? props.attributes[selectedKey] : {}));
    if (!perviousVal) {
        perviousVal = {};
    }
    perviousVal[screen] = { ...perviousVal[screen], ...value };

    setAttributes({
        [selectedKey]: {
            ...data,
            [screen]: value
        },
    });
    // return perviousVal;
}

export const ComponentMap = {
    space_settings: function (selected_attr, props) {
        const marginKey = Object.keys(selected_attr).find(key => key.match(/margin/gi));
        const paddingKey = Object.keys(selected_attr).find(key => key.match(/padding/gi));

        const {
            attributes: { [marginKey]: margin, [paddingKey]: padding },
        } = props;
        return (
            <>
                {(marginKey && margin) && <><DimensionsControl
                    {...props}
                    label={__("Margin", "")}
                    type={"margin"}
                    attrKey={marginKey}
                    defaultSync={false}
                    attrVal={margin}
                    defaultSync={false}
                />
                    <CardDivider /></>}
                <DimensionsControl
                    {...props}
                    label={__("Padding", "")}
                    attrKey={paddingKey}
                    defaultSync={false}
                    attrVal={padding}
                    defaultSync={false}
                />
            </>
        );
    },
    structure_settings: function (selected_attr, props) {
        /**Block Sizing: including property Width/height/MIN/MAX */
        return <BWFBlockSizing {...props} />;
    },
    typography_settings: (selected_attr, props, disable = [], fontStyle = false) => {
        if (!selected_attr) {
            return;
        }
        /**
         * Normal Keys
         */
        const fontKey = Object.keys(selected_attr).find(key => key.match(/font/gi));
        const textKey = Object.keys(selected_attr).find(key => key.match(/text/gi) && !key.match(/font/gi) && !key.match(/lineHeight/gi) && !key.match(/letterSpacing/gi));
        const lineHeightKey = Object.keys(selected_attr).find(key => key.match(/lineHeight/gi));
        const letterSpacingKey = Object.keys(selected_attr).find(key => key.match(/letterSpacing/gi));

        /**
         * Hover Keys
         */
        const fontHoverKey = Object.keys(selected_attr).find(key => key.match(/fontHover/gi));
        const textHoverKey = Object.keys(selected_attr).find(key => key.match(/textHover/gi));
        const lineHeightHoverKey = Object.keys(selected_attr).find(key => key.match(/lineHeightHover/gi));
        const letterSpacingHoverKey = Object.keys(selected_attr).find(key => key.match(/letterSpacingHover/gi));
        const {
            attributes: { [fontKey]: font, [textKey]: text, [lineHeightKey]: lineHeight, [letterSpacingKey]: letterSpacing, [fontHoverKey]: fontHover, [textHoverKey]: textHover, [lineHeightHoverKey]: lineHeightHover, [letterSpacingHoverKey]: letterSpacingHover }, screenType, setScreen
        } = props;
        const screen = getCurrentScreen();
        return (
            <>
                {/* {hoverTab(screenType, setScreen)} */}
                {screenType !== 'hover' ?
                    <TypographySettings
                        font={font && font[screen] ? font[screen] : {}}
                        text={text && text[screen] ? text[screen] : {}}
                        lineHeight={lineHeight && lineHeight[screen] ? lineHeight[screen] : {}}
                        letterSpacing={
                            letterSpacing && letterSpacing[screen] ? letterSpacing[screen] : {}
                        }
                        disable={disable}
                        onChangeFont={(value) => setAttributeVal(fontKey, value, props, screen)}
                        onChangeText={(value) => setAttributeVal(textKey, value, props, screen)}
                        onChangeLineHeight={(value) => setAttributeVal(lineHeightKey, value, props, screen)}
                        onChangeLetterSpacing={(value) => setAttributeVal(letterSpacingKey, value, props, screen)}
                        fontStyle={fontStyle}
                    />
                    :
                    <TypographySettings
                        font={fontHover && fontHover[screen] ? fontHover[screen] : {}}
                        text={textHover && textHover[screen] ? textHover[screen] : {}}
                        lineHeight={lineHeightHover && lineHeightHover[screen] ? lineHeightHover[screen] : {}}
                        letterSpacing={
                            letterSpacingHover && letterSpacingHover[screen] ? letterSpacingHover[screen] : {}
                        }
                        onChangeFont={(value) => setAttributeVal(fontHoverKey, value, props, screen)}
                        onChangeText={(value) => setAttributeVal(textHoverKey, value, props, screen)}
                        onChangeLineHeight={(value) => setAttributeVal(lineHeightHoverKey, value, props, screen)}
                        onChangeLetterSpacing={(value) => setAttributeVal(letterSpacingHoverKey, value, props, screen)}
                    />
                }
            </>
        );
    },
    sectypography_settings: function (selected_attr, props) {
        return this.typography_settings(selected_attr, props);
    },
    labeltypography_settings: function (selected_attr, props) {
        return this.typography_settings(selected_attr, props);
    },
    label_settings: function (selected_attr, props) {
        return this.heading_settings(selected_attr, props)
    },
    input_settings: function (selected_attr, props) {
        return (
            <>
                {this.heading_settings(selected_attr, props)}
                <PanelBody scrollAfterOpen={false} title={__('Border', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.border_settings(
                        pickBy(selected_attr, function (value, key) {
                            return key.match(/border/gi);
                        }), props)}
                </PanelBody>
            </>
        )
    },
    inputtypography_settings: function (selected_attr, props) {
        return this.typography_settings(selected_attr, props);
    },
    background_settings: function (selected_attr, props, disable = [], labelSuffix = '', labelPrefix = '', label) {
        const { screenType, setScreen, setAttributes } = props;
        const backgroundKey = Object.keys(selected_attr).filter(key => key.match(/background/gi) && !key.match(/backgroundhover/gi));
        const backgroundHoverKey = Object.keys(selected_attr).filter(key => key.match(/backgroundhover/gi));

        let keyList = Object.keys(selected_attr);
        let Hover = true;
        if (backgroundKey && backgroundKey) {
            if ('normal' === screenType) {
                keyList = backgroundKey;
            } else {
                keyList = backgroundHoverKey;
            }
        } else {
            Hover = false;
            keyList = Object.keys(selected_attr);
        }

        if (Object.keys(selected_attr).length === 1) {
            Hover = false;
            keyList = Object.keys(selected_attr);
        }
        return (
            <>
                {Hover && hoverTab(screenType, setScreen)}
                {keyList.map((index_key, index) => {
                    const { attributes: { [index_key]: background, screen } } = props;
                    let bgSettings = {};
                    if (!background || !background.hasOwnProperty(screen)) {
                        bgSettings = {
                            color: '',
                            gradient: '',
                            'blend-mode': '',
                        };
                    } else {
                        bgSettings = cloneDeep(background[screen])
                    }
                    return (
                        <Fragment key={index_key}>
                            <BwfBackground
                                labelPrefix={labelPrefix}
                                labelSuffix={labelSuffix}
                                label={label}
                                disable={disable}
                                key={index_key + 'bg'}
                                background={bgSettings}
                                onChange={(value) => setAttributes({ [index_key]: { ...background, ...{ [screen]: value } } })}
                            />
                            {((Object.keys(selected_attr).length - 1) === index) ? null : <CardDivider />}
                        </Fragment>
                    )
                })}
            </>
        )
    },
    progresstext_settings: function (selected_attr, props) {

        return (
            <>

                <PanelBody scrollAfterOpen={false} title={__('Typography', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.typography_settings(
                        selected_attr, props)}
                </PanelBody>
                {/* <PanelBody scrollAfterOpen={false} title={__('Color', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.color_settings(
                        pickBy(selected_attr, function (value, key) {
                            return value.type.match(/color/gi);
                        }), props)}
                </PanelBody> */}
            </>
        )
    },

    progressbar_settings: function (selected_attr, props) {
        const screen = getCurrentScreen();
        let progressWidthKey = 'progressWidth';
        let progressHeightKey = 'progressHeight';
        if (screen && screen !== 'desktop') {
            progressWidthKey = screen === 'mobile' ? 'progressWidthMobile' : 'progressWidthTablet';
            progressHeightKey = screen === 'mobile' ? 'progressHeightMobile' : 'progressHeightTablet';
        }
        const { attributes: { [progressWidthKey]: progressWidth, [progressHeightKey]: progressHeight }, setAttributes } = props
        return <>
            {this.progresstext_settings(
                pickBy(selected_attr, function (value, key) {
                    return !key.match(/background/gi);
                }), props)}
            <PanelBody scrollAfterOpen={false} title={__('Color', 'funnel-builder-powerpack')} initialOpen={false}>
                {this.color_settings(
                    pickBy(selected_attr, function (value, key) {
                        return value.type.match(/color/gi);
                    }), props)}
                {this.background_settings(
                    pickBy(selected_attr, function (value, key) {
                        return value.type.match(/fill-bg/gi);
                    }), props, ['blend-mode', 'image'], '', '', 'Fill Color')}
                {this.background_settings(
                    pickBy(selected_attr, function (value, key) {
                        return value.type.match(/background/gi);
                    }), props, ['blend-mode', 'image'])}
            </PanelBody>

            <PanelBody scrollAfterOpen={false} title={__('Spacing', 'funnel-builder-powerpack')} initialOpen={false}>
                <DimensionsControl
                    {...props}
                    max={100}
                    label={__("Padding", "")}
                    attrKey={'progressBarPadding'}
                    defaultSync={false}
                    attrVal={props.attributes.progressBarPadding}
                    defaultSync={false}
                />
            </PanelBody>
            <PanelBody scrollAfterOpen={false} title={__('Sizing', 'funnel-builder-powerpack')} initialOpen={false}>
                <RangeControl label={__('Width', 'funnel-builder-powerpack')} value={progressWidth} onChange={(progressWidth) => setAttributes({ [progressWidthKey]: progressWidth })} min={0} max={100} />
                <RangeControl label={__('Height', 'funnel-builder-powerpack')} value={progressHeight} onChange={(progressHeight) => setAttributes({ [progressHeightKey]: progressHeight })} min={0} max={100} />
            </PanelBody>
        </>;
    },
    border_settings: function (selected_attr, props, disable = []) {
        return Object.keys(selected_attr).map((index_key, i) => {
            const { attributes: { [index_key]: border }, setAttributes } = props;
            if (i) {
                return null;
            }
            return (
                <Fragment key={index_key}>
                    <BorderComponenet
                        disable={disable}
                        key={index_key}
                        attrKey={index_key}
                        border={JSON.parse(JSON.stringify(border ? border : {}))}
                        onChange={(value) => {
                            setAttributes({
                                [index_key]: value,
                            })
                        }}
                    />
                </Fragment>
            )
        });
    },
    boxShadow_settings: function (selected_attr, props) {
        const { screenType, setScreen } = props;
        let keyList = Object.keys(selected_attr);
        return (
            <>
                {hoverTab(screenType, setScreen)}
                {
                    keyList.map(index_key => {
                        const key = 'normal' === screenType ? !index_key.match(/boxShadowHover/gi) : index_key.match(/boxShadowHover/gi);
                        if (!key) {
                            return null;
                        }
                        const { attributes: { [index_key]: boxShadow } } = props;
                        const screen = getCurrentScreen();
                        return (
                            <Fragment key={index_key}>
                                <BoxShadowComponent
                                    boxShadow={
                                        boxShadow && boxShadow.hasOwnProperty(screen)
                                            ? boxShadow[screen]
                                            : defaultBoxShadowval
                                    }
                                    onChange={(value) => setAttributeVal(index_key, value, props, screen)}
                                />
                            </Fragment>
                        )
                    })
                }
            </>
        );
    },
    color_settings: function (selected_attr, props) {
        return (
            <>
                {
                    Object.keys(selected_attr).map((index_key, i) => {
                        const Title = PanelTitle.find((pane) => pane.key === index_key);
                        const { attributes: { [index_key]: colorObj }, setAttributes } = props;
                        const screen = getCurrentScreen();
                        return (
                            <Fragment key={index_key}>
                                {i !== 0 ? <CardDivider /> : null}
                                <label className="bwf-label" htmlFor={"bwf-button-style"}>
                                    {selected_attr[index_key].label ? selected_attr[index_key].label : (Title && Title["label"] ? Title["label"] : selected_attr[index_key].label)}
                                    <ColorIndicator
                                        className="bwf-color-indicator"
                                        colorValue={colorObj && colorObj[screen] ? colorObj[screen] : ""}
                                    />
                                </label>
                                <ColorPalette
                                    value={colorObj && colorObj[screen] ? colorObj[screen] : ""}
                                    onChange={(value) => setAttributeVal(index_key, value, props, screen)}
                                    disableCustomColors={false}
                                    colors={defaultColors}
                                    clearable={true}
                                />
                            </Fragment>
                        )
                    })
                }
            </>
        );
    },
    seccolor_settings: function (selected_attr, props) {
        return this.color_settings(selected_attr, props);
    },
    asteriskcolor_settings: function (selected_attr, props) {
        return this.color_settings(selected_attr, props);
    },
    popupsubheading_settings: function (selected_attr, props) {
        return this.heading_settings(selected_attr, props, true);
    },
    popupheadingafter_settings: function (selected_attr, props) {
        return this.heading_settings(selected_attr, props, true);
    },
    popupheading_settings: function (selected_attr, props) {
        return (
            <>
                <PanelBody scrollAfterOpen={false} title={__('Typography', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.typography_settings(
                        pickBy(selected_attr, function (value, key) {
                            return value.type.match(/headingtypography/gi);
                        }), props, [], true)}
                </PanelBody>
                {/* <label className="bwf-label" htmlFor={"bwf-button-style"}>{__('Typography')}</label>
                <CardDivider /> */}
                <PanelBody scrollAfterOpen={false} title={__('Color', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.color_settings(
                        pickBy(selected_attr, function (value, key) {
                            return value.type.match(/headingcolor/gi) && !value.type.match(/subheadingcolor/gi);
                        }), props)}
                </PanelBody>
            </>
        )
    },
    heading_settings: function (selected_attr, props, style = false) {
        return (
            <>
                <PanelBody scrollAfterOpen={false} title={__('Typography', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.typography_settings(
                        pickBy(selected_attr, function (value, key) {
                            return !key.match(/typography/gi) && !key.match(/color/gi);
                        }), props, [], style)}
                </PanelBody>
                <PanelBody scrollAfterOpen={false} title={__('Color', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.color_settings(
                        pickBy(selected_attr, function (value, key) {
                            return key.match(/color/gi);
                        }), props)}
                </PanelBody>
            </>
        )
    },
    subheading_settings: function (selected_attr, props) {
        return this.heading_settings(selected_attr, props);
    },
    submitbutton_settings: function (selected_attr, props) {
        let tempKey = Object.keys(selected_attr).find(function (key, i) {
            return key.match(/width/gi);
        });
        let tempKeyHeight = 'buttonHeight'
        tempKey = tempKey ? tempKey : 'buttonWidth';
        tempKeyHeight = tempKeyHeight ? tempKeyHeight : 'buttonHeight';
        const onChangeSize = (newVal, updateKey, originalVal, attrKey) => {
            const { setAttributes, screen } = props;
            let updatedVal = JSON.parse(JSON.stringify(originalVal ? originalVal : {
                [screen]: {
                    [attrKey]: '',
                    'unit': 'px'
                }
            }));
            updatedVal[screen][updateKey] = newVal;
            setAttributes({ [attrKey]: updatedVal });
        }
        const { attributes: { [tempKey]: buttonWidth, buttonHeight, secondaryContentEnable }, screen, screenType, setScreen } = props;
        return (
            <Fragment >
                <PanelBody scrollAfterOpen={false} title={__('Typography', 'funnel-builder-powerpack')} initialOpen={false}>
                    {secondaryContentEnable ?
                        <>
                            <PanelBody scrollAfterOpen={false} title={__('Text', 'funnel-builder-powerpack')} initialOpen={false}>
                                {this.typography_settings(
                                    pickBy(selected_attr, function (value, key) {
                                        return value.type.match(/typographybtn/gi);
                                    }), props)}
                            </PanelBody>
                            <PanelBody scrollAfterOpen={false} title={__('Subtitle', 'funnel-builder-powerpack')} initialOpen={false}>
                                {this.typography_settings(
                                    pickBy(selected_attr, function (value, key) {
                                        return value.type.match(/typographysec/gi);
                                    }), props)}
                            </PanelBody></>
                        : this.typography_settings(
                            pickBy(selected_attr, function (value, key) {
                                return value.type.match(/typographybtn/gi);
                            }), props)}
                </PanelBody>
                <PanelBody scrollAfterOpen={false} title={__('Color', 'funnel-builder-powerpack')} initialOpen={false}>
                    {hoverTab(screenType, setScreen)}
                    {this.color_settings(
                        pickBy(selected_attr, function (value, key) {
                            return (screenType === 'normal' ? !key.match(/ColorHover/gi) && key.match(/color/gi) : key.match(/ColorHover/gi));
                        }), props)}
                    <CardDivider />
                    {this.background_settings(
                        pickBy(selected_attr, function (value, key) {
                            return (screenType === 'normal' ? !key.match(/buttonBackgroundHover/gi) && key.match(/buttonBackground/gi) : key.match(/buttonBackgroundHover/gi));
                        }), props, ['image', 'blend-mode'], '', screenType === 'normal' ? '' : __(' Hover'))}
                </PanelBody>
                <PanelBody scrollAfterOpen={false} title={__('Spacing', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.space_settings(
                        pickBy(selected_attr, function (value, key) {
                            return key.match(/padding/gi) || key.match(/margin/gi);
                        }), props)}
                </PanelBody>
                <PanelBody scrollAfterOpen={false} title={__('Alignment', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.alignment_settings(
                        pickBy(selected_attr, function (value, key) {
                            return key.match(/alignment/gi);
                        }), props)}
                </PanelBody>
                <PanelBody scrollAfterOpen={false} title={__('Sizing', 'funnel-builder-powerpack')} initialOpen={false}>
                    <AdvancedRangeControl
                        label={__('Width', 'funnel-builder-powerpack')}
                        units={['px', 'em', '%', 'vw']}
                        min={0}
                        max={1600}
                        step={1}
                        allowReset={true}
                        value={buttonWidth && buttonWidth[screen] && buttonWidth[screen].hasOwnProperty('width') && buttonWidth[screen].width}
                        unit={buttonWidth && buttonWidth[screen] && buttonWidth[screen].hasOwnProperty('unit') && buttonWidth[screen].unit}
                        onChange={(newVal) => onChangeSize(newVal, 'width', buttonWidth, tempKey)}
                        onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', buttonWidth, tempKey)}
                    />
                    <AdvancedRangeControl
                        label={__('Height', 'funnel-builder-powerpack')}
                        units={['px', 'em', '%', 'vw']}
                        min={0}
                        max={1600}
                        step={1}
                        allowReset={true}
                        value={buttonHeight && buttonHeight[screen] && buttonHeight[screen].hasOwnProperty('height') && buttonHeight[screen].height}
                        unit={buttonHeight && buttonHeight[screen] && buttonHeight[screen].hasOwnProperty('unit') && buttonHeight[screen].unit}
                        onChange={(newVal) => onChangeSize(newVal, 'height', buttonHeight, 'buttonHeight')}
                        onChangeUnit={(newUnit) => onChangeSize(newUnit, 'unit', buttonHeight, 'buttonHeight')}
                    />
                </PanelBody>
                <PanelBody scrollAfterOpen={false} title={__('Border', 'funnel-builder-powerpack')} initialOpen={false}>
                    {this.border_settings(
                        pickBy(selected_attr, function (value, key) {
                            return key.match(/border/gi);
                        }), props)}
                </PanelBody>
            </Fragment>
        )
    },
    popupbtn_settings: function (selected_attr, props) {

        return <>{this.submitbutton_settings(selected_attr, props)}
            <PanelBody scrollAfterOpen={false} title={__('Box Shadow', 'funnel-builder-powerpack')} initialOpen={false}>
                {this.boxShadow_settings({
                    buttonBoxShadow: {
                        type: 'boxShadow',
                        group: 'popupbtn',
                        label: __('Box Shadow', 'funnel-builder-powerpack'),
                    },
                    buttonBoxShadowHover: {
                        type: 'boxShadow',
                        group: 'popupbtn',
                        label: __('Box Shadow Hover', 'funnel-builder-powerpack'),
                    }
                }, props)}
            </PanelBody>
        </>;
    },
    alignment_settings: function (selected_attr, props) {
        const { attributes: { alignment }, setAttributes, screen } = props
        const textAlignSetting = [
            {
                id: 'left',
                icon: 'editor-alignleft',
                label: __('Left', 'funnel-builder-powerpack'),
            },
            {
                id: 'center',
                icon: 'editor-aligncenter',
                label: __('Center', 'funnel-builder-powerpack'),
            },
            {
                id: 'right',
                icon: 'editor-alignright',
                label: __('Right', 'funnel-builder-powerpack'),
            },
        ];
        return (
            <>
                <label className={"bwf-label"} htmlFor={"bwf-button"}>{__('Alignment')}</label>
                <div className={'bwf-responsive-tabs-list bwf-relative'}>
                    {textAlignSetting.map((TData) => {
                        return (
                            <Button
                                key={TData.id}
                                onClick={() => {
                                    setAttributes({
                                        alignment: {
                                            ...alignment,
                                            [screen]: TData.id,
                                        },
                                    });
                                }}
                                className={classNames('bwf-screen-tabs', {
                                    'active-screen':
                                        alignment &&
                                        alignment[screen] === TData.id,
                                })}
                            >
                                <Tooltip
                                    position="top center"
                                    text={TData.label}
                                >
                                    <span className={'bwf-device-brick'}>
                                        <Dashicon icon={TData.icon} />
                                    </span>
                                </Tooltip>
                            </Button>
                        );
                    })}
                </div>
            </>
        )
    },
    popupcross_settings: function (selected_attr, props) {
        const { attributes: { crossFont, crossPadding, closeVertical, closeHorizontal }, setAttributes, screenType, setScreen } = props;
        const screen = getCurrentScreen();
        const size = crossFont && crossFont.hasOwnProperty(screen) && crossFont[screen].hasOwnProperty('size') ? crossFont[screen] : { 'size': '', 'unit': 'px' };

        const vertical = closeVertical[screen];
        const horizontal = closeHorizontal[screen];
        return (
            <>
                <label htmlFor={"bwf-close-btn-button"} className={"bwf-label"}>{__('Position', 'funnel-builder-powerpack')}</label>
                <AdvancedRangeControl
                    label={__('Vertical', 'funnel-builder-powerpack')}
                    units={['px', 'em']}
                    min={-1000}
                    max={1000}
                    step={1}
                    allowReset={true}
                    value={vertical['width']}
                    unit={vertical.size && vertical.hasOwnProperty('unit') && vertical.unit}
                    onChange={(newVal) => {
                        let val = JSON.parse(JSON.stringify(closeVertical ? closeVertical : {}));
                        if (!val.hasOwnProperty(screen)) {
                            val = { [screen]: { 'unit': 'px' } };
                        } else if (typeof val[screen] !== 'object') {
                            val[screen] = {};
                        }
                        val[screen]['width'] = newVal;
                        setAttributes({ closeVertical: val });
                    }}
                    onChangeUnit={(newUnit) => {
                        let val = JSON.parse(JSON.stringify(closeVertical ? closeVertical : {}));
                        if (!val.hasOwnProperty(screen)) {
                            val = { [screen]: { 'unit': 'px' } };
                        } else if (typeof val[screen] !== 'object') {
                            val[screen] = {};
                        }
                        val[screen]['unit'] = newUnit;
                        setAttributes({ closeVertical: val });
                    }}
                />
                <AdvancedRangeControl
                    label={__('Horizontal', 'funnel-builder-powerpack')}
                    units={['px', 'em']}
                    min={-1000}
                    max={1000}
                    step={1}
                    allowReset={true}
                    value={horizontal['width']}
                    unit={horizontal.size && horizontal.hasOwnProperty('unit') && horizontal.unit}
                    onChange={(newVal) => {
                        let val = JSON.parse(JSON.stringify(closeHorizontal ? closeHorizontal : {}));
                        if (!val.hasOwnProperty(screen)) {
                            val = { [screen]: { 'unit': 'px' } };
                        } else if (typeof val[screen] !== 'object') {
                            val[screen] = {};
                        }
                        val[screen]['width'] = newVal;
                        setAttributes({ closeHorizontal: val });
                    }}
                    onChangeUnit={(newUnit) => {
                        let val = JSON.parse(JSON.stringify(closeHorizontal ? closeHorizontal : {}));
                        if (!val.hasOwnProperty(screen)) {
                            val = { [screen]: { 'unit': 'px' } };
                        } else if (typeof val[screen] !== 'object') {
                            val[screen] = {};
                        }
                        val[screen]['unit'] = newUnit;
                        setAttributes({ closeHorizontal: val });
                    }}
                />
                <CardDivider />
                <label htmlFor={"bwf-close-btn-button"} className={"bwf-label"}>{__('Size', 'funnel-builder-powerpack')}</label>
                <div className="bwf-component--fontsize">
                    <AdvancedRangeControl
                        label={__('Font Size', 'funnel-builder-powerpack')}
                        units={['px', 'em']}
                        min={0}
                        max={200}
                        step={1}
                        allowReset={true}
                        value={size['size']}
                        unit={size && size.hasOwnProperty('unit') && size.unit}
                        onChange={(newVal) => {
                            let val = JSON.parse(JSON.stringify(crossFont ? crossFont : {}));
                            if (!val.hasOwnProperty(screen)) {
                                val = { [screen]: { 'unit': 'px' } };
                            } else if (typeof val[screen] !== 'object') {
                                val[screen] = {};
                            }
                            val[screen]['size'] = newVal;
                            setAttributes({ crossFont: val });
                        }}
                        onChangeUnit={(newUnit) => {
                            let val = JSON.parse(JSON.stringify(crossFont ? crossFont : {}));
                            if (!val.hasOwnProperty(screen) || typeof val !== 'object') {
                                val = { [screen]: { 'size': '', 'unit': 'px' } };
                            } else if (typeof val[screen] !== 'object') {
                                val[screen] = {};
                            }
                            val[screen]['unit'] = newUnit;
                            setAttributes({ crossFont: val });
                        }}
                    />
                </div>
                <DimensionsControl
                    {...props}
                    label={__("Padding", "")}
                    attrKey={'crossPadding'}
                    defaultSync={false}
                    attrVal={JSON.parse(JSON.stringify(crossPadding))}
                />
                {this.border_settings(
                    pickBy(selected_attr, function (value, key) {
                        return key.match(/border/gi);
                    }), props, ['border-width', 'border-color', 'border-style'])}

                <CardDivider />
                {hoverTab(screenType, setScreen)}
                {this.color_settings(
                    pickBy(selected_attr, function (value, key) {
                        return (screenType === 'normal' ? !key.match(/crossColorHover/) && key.match(/crossColor/) : key.match(/crossColorHover/));
                    }), props)}
                {this.background_settings(
                    pickBy(selected_attr, function (value, key) {
                        return (screenType === 'normal' ? !key.match(/crossBackgroundColorHover/) && key.match(/crossBackgroundColor/) : key.match(/crossBackgroundColorHover/));
                    }), props, ['image', 'blend-mode'], '', screenType === 'normal' ? '' : __(' Hover'))}
            </>
        )
    }
};
