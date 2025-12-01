/* eslint-disable jsx-a11y/anchor-is-valid */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { omit } from 'lodash';

/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InnerBlocks, RichText, AlignmentToolbar, BlockAlignmentToolbar, useBlockProp, BlockControls } from '@wordpress/block-editor';
import { Button, Dropdown, Icon, Toolbar, ToolbarButton } from '@wordpress/components';
import { addFilter, applyFilters } from "@wordpress/hooks";
import { compose } from "@wordpress/compose";
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import BWFGeneral from './general';
import { fontCheck, useUniqueId } from 'BWFOP/utils/utils';
import BWFBlocksInspectorControlInner from 'BWFOP/inspector/blocks-inspector-inner';
import BWFBlockStyle from './css';
import { SvgIcon } from 'BWFOP/components';
import BlockAttributesType from './block-attributes';
import { BackgroundToolbar, TypograpghyToolbar, ColorToolbar } from "BWFOP/components/block-toolbar";
import { getCurrentScreen } from "BWFOP/utils/utils";
import { toolbarBGIndicator } from "BWFOP/utils/utils";
import { BwfBackground } from "BWFOP/components";

const TEMPLATE = [
	['bwfblocks/optin-form', {}],
];

const uniqueIDArr = [];
const Edit = (props) => {
	const {
		attributes: {
			type,
			content,
			button,
			uniqueID,
			blockID,
			secondaryContentEnable,
			secondaryContent,
			heading,
			subHeading,
			progressBarText,
			progressBarEnable,
			textAfter,
			progressStripEnable,
			progressBarTextEnable,
			alignment,
			text,
			popupType,
			buttonBackground,
		},
		className,
		clientId,
		setAttributes,
		name,
		isSelected,
		selectblocks
	} = props;

	const blockName = name.replace(/^\w+\//g, '');

	useEffect(() => {
		if (!uniqueID) {
			setAttributes({ progressBarEnable: true });
			setAttributes({ progressStripEnable: true });
			setAttributes({ progressBarTextEnable: false });
		}
	}, []);
	useUniqueId(props)

	const btnClass = 'bwf-btn bwf-btn-popup btn-' + type;
	const screen = getCurrentScreen();

	addFilter(
		`bwf.${blockName}.attributesStructure`,
		`${name}attributesStructure`,
		function (AttributesType) {
			const btnAttr = { ...BlockAttributesType };
			if (!secondaryContentEnable) {
				if (!progressBarEnable) {
					return omit(btnAttr, ['zIndex', 'secondaryColor', 'secondaryColorHover', 'progressbar']);
				}
				return omit(btnAttr, ['zIndex', 'secondaryColor', 'secondaryColorHover']);
			}
			if (!progressBarEnable) {
				return omit(btnAttr, ['zIndex', 'progressbar']);
			}
			return omit(btnAttr, ['zIndex']);
		}
	);

	addFilter(
		`bwf.${blockName}.attributesTitle`,
		`${name}attributesTitle`,
		function (Label, key) {
			if (key === 'space') {
				return 'Spacing';
			}
			if (key === 'structure') {
				return 'Sizing';
			}
			if (key === 'border') {
				return 'Border';
			}
			if (key === 'background') {
				return 'Background';
			}
			if (key === 'boxShadow') {
				return 'Box Shadow';
			}
			return Label;
		}
	);
	const [active, setActive] = useState(false)
	const btnClassWrap = applyFilters(`bwf.${blockName}.classname`, 'bwfop-poup-button-wrap');
	const btnPOP = active ? 'show_popup_form ' : '';
	const { getBlock } = selectblocks;
	const innerBlock = getBlock(clientId);
	let innerFormID = ''
	if (innerBlock && innerBlock.innerBlocks && innerBlock.innerBlocks.length) {
		innerFormID = innerBlock.innerBlocks[0].clientId;
	}

	return (
		<>
			 <style>
			 {active ?
				
					`.components-popover.block-editor-block-list__block-popover .components-popover__content .block-editor-block-contextual-toolbar{
						visibility: hidden;
					}
					.bwf-section-wrap .bwf-col .wp-block-columns, .bwf-section-wrap .bwf-col>.block-editor-inner-blocks>.block-editor-block-list__layout{
						z-index:unset;
					}
					`
				: 
				`.bwf_pp_overlay {visibility: hidden}`
			}
			</style>
			<BlockControls>
				<TypograpghyToolbar {...props} disable={['text-align']} />
				<ColorToolbar {...props} label={__('Text Color')} />
				<Toolbar>
					<Dropdown
						className="components-dropdown-menu components-toolbar"
						contentClassName="bwf-block-toolbar"
						position="top center"
						renderToggle={({ isOpen, onToggle }) => (
							<Button
								className="bwf-color-indicator-toolbar"
								label={'Button Color'}
								title={'Button Color'}
								onClick={onToggle}
								aria-expanded={isOpen}
							>
								<span className="component-color-indicator bwf-color-indicate" style={toolbarBGIndicator(buttonBackground, screen)}></span>
							</Button>
						)}
						renderContent={() => (
							<>
								<BwfBackground
									backgroundLabel={'Button'}
									background={buttonBackground && buttonBackground[screen]}
									onChange={(value) => {
										const tempBackground = JSON.parse(JSON.stringify(buttonBackground ? buttonBackground : {}));
										tempBackground[screen] = value;
										setAttributes({ buttonBackground: tempBackground });
									}}
									disable={['blend-mode', 'image']}
								/>
								{<div>Go to <i>Style {'>'} Call to Action Button</i> for more settings.</div>}
							</>
						)}
					/>
				</Toolbar>

				{/*<BackgroundToolbar*/}
				{/*	{...props}*/}
				{/*	label={ __( 'Background Color' ) }*/}
				{/*	attrKey={ 'buttonBackground' }*/}
				{/*	disable={['image', 'blend-mode']}*/}
				{/*/>*/}
				<BlockAlignmentToolbar
					value={
						alignment && alignment[screen]
							? alignment[screen]
							: ''
					}
					controls={['left', 'center', 'right']}
					onChange={(align) => {
						const temp = lodash.cloneDeep(alignment ? alignment : {});
						if (!alignment[screen]) {
							alignment[screen] = {};
						}
						temp[screen] = align;
						setAttributes({ alignment: temp });
					}}
				/>
			</BlockControls>
			<BWFBlocksInspectorControlInner {...props} BWFGeneral={BWFGeneral} innerFormID={innerFormID} />
			<BWFBlockStyle {...props} />
			<div className={`bwfop-popup-form-container bwf-${uniqueID}`} id={blockID}>
				<div className={`bwf_pp_overlay ${popupType} ${btnPOP}`}>
					<div className={`bwf_pp_wrap bwfop-popup-bg bwf-popup-${uniqueID}`}>
						<div className="bwf_pp_close bwf-close" onClick={() => setActive(false)}>×</div>
						<div className={`bwf_pp_cont `}>
							<div className="bwf-poup-inner-wrap">
								<div className="bwf-popup-head">
									<div className={"bwf-inner"}>
										{progressBarEnable ? <>
											{progressBarTextEnable ? <div className="pp-bar-text above">{progressBarText ? progressBarText : '75% Complete'}</div> : null}
											<div className="bwf-pp-bar-wrap">
												<span className={classNames("bwf-pp-bar", {
													"bwf-pp-animate": progressStripEnable
												})} role="progressbar">
													{!progressBarTextEnable ? <span className="pp-bar-text inside">{progressBarText ? progressBarText : '75% Complete'}</span> : null}
												</span>
											</div></> : null}
										<div className="bwf-heading-wrap">
											{heading && <RichText
												tagName={'div'}
												className="bwf-heading"
												value={heading}
												onChange={(newVal) => {
													setAttributes({ heading: newVal });
												}}
												placeholder={'Add Text...'}
												allowedFormats={['']}
											/>}
											{subHeading && <RichText
												tagName={'div'}
												className="bwf-subheading"
												value={subHeading}
												onChange={(newVal) => {
													setAttributes({ subHeading: newVal });
												}}
												placeholder={'Add Text...'}
												allowedFormats={['']}
											/>}
										</div>
										<InnerBlocks
											template={TEMPLATE}
											templateLock="all"
										/>
										{textAfter && <RichText
											tagName={'div'}
											className="bwf-pp-footer"
											value={textAfter}
											allowedFormats={['']}
											onChange={(newVal) => {
												setAttributes({ textAfter: newVal });
											}}
											placeholder={'Add Text...'}
										/>}
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>
				<div className={`bwf-btn-wrap ${btnClassWrap}`}>
					<div
						className={classNames(btnClass, className)}
						onClick={() => { setActive(true) }}
					>
						<span className="bwf-btn-inner-text btn-icon-text-wrap">
							{!!button && !!button.icon && 'left' === button.iconPos && (
								<SvgIcon
									value={
										button.icon
									}
								/>
							)}
							<RichText
								tagName={'span'}
								value={content}
								onChange={(newVal) => {
									setAttributes({ content: newVal });
								}}
								placeholder={'Add Button Text...'}
							/>
							{!!button && !!button.icon && 'right' === button.iconPos && (
								<SvgIcon
									value={
										button.icon
									}
								/>
							)}
						</span>
						{secondaryContentEnable && (
							<RichText
								tagName={'span'}
								className="bwf-btn-sub-text"
								value={secondaryContent}
								onChange={(newVal) => {
									setAttributes({ secondaryContent: newVal });
								}}
								placeholder={'Add Secondary Text...'}
							/>
						)}
					</div>
				</div>
			</div>
		</>
	);
};
export default compose([
	withSelect((select, ownProps) => {
		return {
			selectblocks: select('core/block-editor'),
		};
	})])(Edit);