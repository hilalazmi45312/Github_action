/**
 * WordPress depedencies
 */
import { sprintf, __ } from "@wordpress/i18n";
import { Fragment } from "@wordpress/element";
import {
    Button,
    RangeControl,
    TextControl,
    ToggleControl,
    ButtonGroup,
    PanelBody, SelectControl, TextareaControl,
} from "@wordpress/components";
import { applyFilters } from "@wordpress/hooks";
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch, useDispatch } from "@wordpress/data";

/**
 * Internal depednencies
 */
import { IconControl, UnitSelector, BWFBlockID } from "BWFOP/components";

const popupTypeList = [
    {
        value: 'bwf_pp_effect_fade',
        label: __('Fade', 'funnel-builder-powerpack'),
    },
    {
        value: 'bwf_pp_effect_slide-up',
        label: __('Slide Up', 'funnel-builder-powerpack'),
    },
    {
        value: 'bwf_pp_effect_slide-down',
        label: __('Slide Down', 'funnel-builder-powerpack'),
    },
]

/**
 * Block GENERAL TAB setting Component
 *
 * @param {Object} props
 * @return
 */
const BWFGeneral = (props) => {
    const {
        attributes: {
            button,
            heading,
            subHeading,
            progressBarText,
            progressBarEnable,
            progressStripEnable,
            progressBarTextEnable,
            content,
            secondaryContentEnable,
            secondaryContent,
            contentSpace,
            progressHeight,
            progressWidth,
            textAfter,
            popupType,
        },
        setAttributes,
        name,
        innerFormID,
    } = props;

    const blockName = name.replace(/^\w+\//g, '');
    const Label = applyFilters(`bwf.${blockName}.label`, __("Popup Form", 'funnel-builder-powerpack'));
    const { selectBlock, selectionChange } = useDispatch(blockEditorStore);
    return (
        <Fragment>

            {
                // eslint-disable-next-line jsdoc/require-param
                /**
                 * Button Icon Block Settings
                 */
                <PanelBody scrollAfterOpen={false} title={__('Call to Action Button', 'funnel-builder-powerpack')} initialOpen={false} className="bwfop-button-settings">
                    {/** Button Text */}
                    <TextControl
                        label={__('Text', 'funnel-builder-powerpack')}
                        value={content}
                        onChange={(newContent) => setAttributes({ content: newContent })}
                    />
                    {/** Enable Button Secondary Text */}
                    <ToggleControl
                        label={__("Enable Subtitle Text", 'funnel-builder-powerpack')}
                        checked={secondaryContentEnable}
                        onChange={() =>
                            setAttributes({
                                secondaryContentEnable: !secondaryContentEnable,
                            })
                        }
                    />
                    {secondaryContentEnable && (
                        <>
                            <TextControl
                                label={__('Subtitle Text', 'funnel-builder-powerpack')}
                                value={secondaryContent}
                                onChange={(newContent) =>
                                    setAttributes({ secondaryContent: newContent })
                                }
                            />
                            <div className={"bwf-grid-field"}>
                                <RangeControl
                                    min={0}
                                    max={50}
                                    allowReset={true}
                                    initialPosition={0}
                                    label={__(
                                        "Spacing",
                                        "bwf-gutenberg-block"
                                    )}
                                    value={
                                        contentSpace && contentSpace.value ? contentSpace.value : ""
                                    }
                                    onChange={(space) =>
                                        setAttributes({
                                            contentSpace: {
                                                ...contentSpace,
                                                ...{ value: space },
                                            },
                                        })
                                    }
                                />
                                <UnitSelector
                                    selected={
                                        contentSpace && contentSpace.unit ? contentSpace.unit : "px"
                                    }
                                    units={["px", "em"]}
                                    onChange={(newUnit) =>
                                        setAttributes({
                                            contentSpace: {
                                                ...contentSpace,
                                                ...{ unit: newUnit },
                                            },
                                        })
                                    }
                                />
                            </div>
                        </>
                    )}
                    <IconControl
                        label={__("Icon", 'funnel-builder-powerpack')}
                        value={button ? button.icon : ""}
                        onChange={(newIcon) => {
                            setAttributes({
                                button: { ...button, ...{ icon: newIcon } },
                            });
                        }}
                    />
                    {button && button.icon ? <>
                        {/**Button icon size setting */}
                        <RangeControl
                            min={0}
                            max={60}
                            label={__("Icons size:", "bwf-gutenberg-block")}
                            onChange={(size) =>
                                setAttributes({
                                    button: { ...button, ...{ iconSize: size } },
                                })
                            }
                        />

                        {/**Icon and button gap setting */}
                        <RangeControl
                            min={0}
                            max={50}
                            initialPosition={8}
                            label={__("Icons Space:", "bwf-gutenberg-block")}
                            onChange={(space) =>
                                setAttributes({
                                    button: { ...button, ...{ iconSpace: space } },
                                })
                            }
                        />

                        {/** Icon position settion */}
                        <label htmlFor={"bwf-button-style"}>{__("Icon Position")}</label>
                        <br />
                        <ButtonGroup className={"bwf-button-group"}>
                            <Button
                                isPrimary={button && "left" === button.iconPos ? true : false}
                                onClick={() =>
                                    setAttributes({
                                        button: {
                                            ...button,
                                            ...{ iconPos: "left" },
                                        },
                                    })
                                }
                            >
                                {__("Left")}
                            </Button>
                            <Button
                                isPrimary={button && "right" === button.iconPos ? true : false}
                                onClick={() =>
                                    setAttributes({
                                        button: {
                                            ...button,
                                            ...{ iconPos: "right" },
                                        },
                                    })
                                }
                            >
                                {__("Right")}
                            </Button>
                        </ButtonGroup>
                    </> : null}

                </PanelBody>
            }
            <PanelBody scrollAfterOpen={false} title={__('Popup Progress Bar', 'funnel-builder-powerpack')} className="bwf-progress-settings" initialOpen={false}>
                <ToggleControl
                    label={__("Enable", 'funnel-builder-powerpack')}
                    checked={progressBarEnable}
                    onChange={() =>
                        setAttributes({
                            progressBarEnable: !progressBarEnable,
                        })
                    }
                />
                {progressBarEnable ? <>
                    <ToggleControl
                        label={__("Show progress text above the bar", 'funnel-builder-powerpack')}
                        checked={progressBarTextEnable}
                        onChange={() =>
                            setAttributes({
                                progressBarTextEnable: !progressBarTextEnable,
                            })
                        }
                    />
                    <ToggleControl
                        label={__("Enable Progress bar Animation", 'funnel-builder-powerpack')}
                        checked={progressStripEnable}
                        onChange={() =>
                            setAttributes({
                                progressStripEnable: !progressStripEnable,
                            })
                        }
                    />
                    <TextControl
                        label={__('Text', 'funnel-builder-powerpack')}
                        value={progressBarText}
                        onChange={(newContent) => setAttributes({ progressBarText: newContent })}
                    />
                </> : null}
            </PanelBody>
            <PanelBody scrollAfterOpen={false} title={__('Popup Heading', 'funnel-builder-powerpack')} className="bwf-heading-settings" initialOpen={false}>
                <TextControl
                    label={__('Heading', 'funnel-builder-powerpack')}
                    value={heading}
                    onChange={(newContent) => setAttributes({ heading: newContent })}
                />
                <TextareaControl
                    label={__('Sub Heading', 'funnel-builder-powerpack')}
                    value={subHeading}
                    onChange={(newContent) =>
                        setAttributes({ subHeading: newContent })
                    }
                />
                <TextControl
                    label={__('Text After Button', 'funnel-builder-powerpack')}
                    value={textAfter}
                    onChange={(newContent) =>
                        setAttributes({ textAfter: newContent })
                    }
                />
            </PanelBody>

            {innerFormID ?
                <PanelBody scrollAfterOpen={false} title={__('Popup Form', 'funnel-builder-powerpack')} initialOpen={false} onToggle={() => {
                    try {
                        // selectionChange(innerFormID);
                        dispatch("core/editor").selectBlock(innerFormID, -1)
                    } catch (e) {
                        console.log("Error => ", e);
                    }
                }}>
                </PanelBody>
                : null}
            <PanelBody scrollAfterOpen={false} title={__('Popup Effect', 'funnel-builder-powerpack')} initialOpen={false} className="bwfop-popup-settings">
                <SelectControl
                    label={__('Effect', 'funnel-builder-powerpack')}
                    value={popupType} // e.g: value = [ 'a', 'c' ]
                    onChange={(popupType) => setAttributes({ popupType })}
                    options={popupTypeList}
                />
            </PanelBody>
        </Fragment>
    );
};

export default BWFGeneral;
