/**
 * External dependencies
 */
import { isEmpty, noop } from 'lodash';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Button, Card, CardDivider, Popover } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const UnitSelector = (props) => {
	const [isOpen, toggleUnit] = useState(false);
	const { units, selected, onChange, title } = props;
	const unitList = isEmpty(units) ? ['px', 'em', '%', 'vh', 'vw'] : units;

	return (
		<div className={'bwf-unit-selector'}>
			<Button
				className="bwf-btn-small"
				title={title}
				onClick={() => toggleUnit(!isOpen)}
			>
				{selected}
			</Button>
			{isOpen && (
				<Popover
					position="bottom right"
					focusOnMount={false}
					className={'bwf-unit-dropdown'}
					onFocusOutside={() => {
						toggleUnit(false);
					}}
				>
					<Card size="small">
						{unitList.map((unit) => {
							return (
								<div key={unit} size="small">
									<Button
										onClick={() => {
											onChange(unit);
											toggleUnit(false);
										}}
										className={classNames({
											'bwf-active-btn': selected === unit,
										})}
									>
										{unit}
									</Button>
									<CardDivider />
								</div>
							);
						})}
					</Card>
				</Popover>
			)}
		</div>
	);
};
export default UnitSelector;
