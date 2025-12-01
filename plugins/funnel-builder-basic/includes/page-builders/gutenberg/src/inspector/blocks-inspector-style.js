import { PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { applyFilters } from "@wordpress/hooks";
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch, useDispatch } from "@wordpress/data";

import { ComponentMap } from "../utils/components-map";
import { AttributesType, objectGrouped, PanelTitle } from "../utils/utils";
import { Fragment } from "@wordpress/element";


const BWFStyle = props => {
    const { attributes: { uniqueID }, name, parentBlockId, innerFormID } = props;
    const blockName = name.replace(/^\w+\//g, '');

    const attributesStructure = applyFilters(`bwf.${blockName}.attributesStructure`, AttributesType);

    const PopupBlock = {
        'popupbtn': __('Call to Action Button', 'funnel-builder-powerpack'),
        'progressbar': __('Popup Progress Bar', 'funnel-builder-powerpack'),
        'popupheading': __('Popup Heading', 'funnel-builder-powerpack'),
        'popupsubheading': __('Popup Sub Heading', 'funnel-builder-powerpack'),
        'popupheadingafter': __('Popup Text After', 'funnel-builder-powerpack'),
        'popupcross': __('Popup Close Button', 'funnel-builder-powerpack'),
    };

    let StyleObj = objectGrouped(
        attributesStructure,
        "group"
    );
    let mainTabTitle = null, popupClass = null;
    if (name === 'bwfblocks/popup-form' && innerFormID) {
        mainTabTitle = __('Popup', 'funnel-builder-powerpack')
        popupClass = `.bwf-popup-${uniqueID} .bwf-popup-head`;
    } else {
        mainTabTitle = parentBlockId ? __('Popup Form', 'funnel-builder-powerpack') : null;
    }

    let PopupListData = [];
    const StyleData = () => {
        return Object.keys(StyleObj).map((key, i) => {
            const Title = PanelTitle.find((pane) => pane.key === key);
            let Label = Title ? Title["label"] : key;
            Label = applyFilters(`bwf.${blockName}.attributesTitle`, Label, key);

            const open = Object.keys(PopupBlock).includes(key) ? true : !i;
            const Settings = (
                <PanelBody scrollAfterOpen={false}
                    title={Label}
                    key={key}
                    initialOpen={false}
                    className={`bwf-${key ? key : 'popup'}`}
                >
                    {
                        ComponentMap.hasOwnProperty(`${key}_settings`) ?
                            ComponentMap[`${key}_settings`](StyleObj[key], props) :
                            Object.keys(StyleObj[key]).map((indexKey) => {
                                const { type } = StyleObj[key][indexKey];
                                return (
                                    <div title={indexKey} key={indexKey}>
                                        {
                                            ComponentMap.hasOwnProperty(`${type}_settings`)
                                                ? ComponentMap[`${type}_settings`](
                                                    StyleObj[key][indexKey],
                                                    props)
                                                : null
                                        }
                                    </div>
                                );
                            })
                    }
                </PanelBody>
            )
            if (Object.keys(PopupBlock).includes(key)) {
                PopupListData.push(Settings);
                return <Fragment key={key}></Fragment>
            }
            return <Fragment key={key}>{Settings}</Fragment>;
        });
    }

    const { selectBlock, selectionChange } = useDispatch(blockEditorStore);
    const StyleList = StyleData();
    return (
        <>
            {
                parentBlockId ?
                    <Fragment>
                        {
                            Object.keys(PopupBlock).map((block, index) => {
                                return <PanelBody scrollAfterOpen={false} title={PopupBlock[block]} key={block} initialOpen={false} onToggle={() => {
                                    try {

                                        // dispatch("core/editor").selectBlock(parentBlockId, null)
                                        selectionChange(parentBlockId)
                                        setTimeout(() => {
                                            const tabPanel = document.querySelector('.components-button.components-tab-panel__tabs-item.bwf-tab-buttons.tab-style');
                                            if (tabPanel) {
                                                tabPanel.click();
                                                if (index !== 0) {
                                                    const buttonBar = document.querySelector(`.bwf-${block} button`);
                                                    if (buttonBar) {
                                                        buttonBar.click();
                                                        // buttonBar.scrollIntoView(false);
                                                    }
                                                }
                                            }
                                        }, 100)
                                    } catch (e) {
                                        console.log("Error => ", e);
                                    }
                                }}><></>
                                </PanelBody>
                            })
                        }
                        <PanelBody scrollAfterOpen={false} title={__('Popup', 'funnel-builder-powerpack')} className="bwf-popup-settings" initialOpen={false} onToggle={() => {
                            try {
                                selectionChange(parentBlockId)
                                // dispatch("core/editor").selectBlock(parentBlockId, null)
                                setTimeout(() => {
                                    const tabPanel = document.querySelector('.components-button.components-tab-panel__tabs-item.bwf-tab-buttons.tab-style');
                                    if (tabPanel) {
                                        tabPanel.click();
                                        const buttonBar = document.querySelector(`.bwf-popup button`);
                                        if (buttonBar) {
                                            buttonBar.click();
                                            buttonBar.scrollIntoView({ block: 'start' });
                                        }
                                    }
                                }, 100)
                            } catch (e) {
                                console.log("Error => ", e);
                            }
                        }}><></>
                        </PanelBody>
                    </Fragment>
                    : <></>
            }

            {mainTabTitle ?
                <>
                    {
                        PopupListData && PopupListData.length ? PopupListData.map((data, i) => <Fragment key={i + 1}>{data}</Fragment>) : <></>
                    }
                    <PanelBody scrollAfterOpen={false} title={mainTabTitle} initialOpen={false} className={`bwf-popup`}>
                        {/**
                     * Style Settings
                     *
                     * @param  value
                     */}
                        {StyleList}
                    </PanelBody>
                </> :
                <>{StyleList}</>
            }

            {name === 'bwfblocks/popup-form' && innerFormID ?
                <PanelBody scrollAfterOpen={false} title={__('Popup Form', 'funnel-builder-powerpack')} initialOpen={false} onToggle={() => {
                    try {
                        // let top = null;
                        // if ( popupClass && document.querySelector(popupClass) ) {
                        //     top = document.querySelector(popupClass).scrollTop;
                        // }
                        selectionChange(innerFormID)
                        // dispatch("core/editor").selectBlock(innerFormID, null )
                        setTimeout(() => {
                            const tabPanel = document.querySelector('.components-button.components-tab-panel__tabs-item.bwf-tab-buttons.tab-style');
                            if (tabPanel) {
                                tabPanel.click();
                                const buttonBar = document.querySelector(`.bwf-popup button`);
                                if (buttonBar) {
                                    buttonBar.click();
                                    buttonBar.scrollIntoView(true);
                                }
                            }
                        }, 100)
                    } catch (e) {
                        console.log("Error => ", e);
                    }
                }}>
                </PanelBody>
                : null}
        </>
    )
}
export default BWFStyle;
