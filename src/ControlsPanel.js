import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	TextControl,
	ToggleControl,
	CheckboxControl,
	BaseControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import ControlsPanelHeader from './ControlsPanelHeader';

const ControlsPanel = ({ attributes, setAttributes, ifPageTypes, userRoles }) => {
	const { conditions, exclusion } = attributes;

	console.log("Current attributes: ", attributes);

	const updateConditions = (key, value) => {
		const newConditions = {
			...conditions,
			[key]: {
				...conditions[key],
				properties: value,
			},
		};
		setAttributes({ conditions: newConditions });
	};

	const isAnyConditionEnabled = () => {
		return Object.values(conditions).some(condition => condition.enable);
	};

	return (
		<>
			<ControlsPanelHeader
				attributes={attributes}
				setAttributes={setAttributes}
			/>

			{isAnyConditionEnabled() && (
				<BaseControl className={'vkdif'}>
					<BaseControl>
						{conditions.conditionPageType.enable && (
							<SelectControl
								label={__('Page Type', 'vk-dynamic-if-block')}
								value={conditions.conditionPageType.properties.ifPageType}
								options={ifPageTypes}
								onChange={(value) => updateConditions('conditionPageType', { ifPageType: value })}
							/>
						)}

						{conditions.conditionPostType.enable && (
							<SelectControl
								label={__('Post Type', 'vk-dynamic-if-block')}
								value={conditions.conditionPostType.properties.ifPostType}
								options={vk_dynamic_if_block_localize_data.postTypeSelectOptions}
								onChange={(value) => updateConditions('conditionPostType', { ifPostType: value })}
							/>
						)}

						{conditions.conditionUserRole.enable && (
							<BaseControl
								label={__('User Role', 'vk-dynamic-if-block')}
								help={__('If unchecked, no restrictions are imposed by user role', 'vk-dynamic-if-block')}
							>
								{userRoles.map((role, index) => (
									<CheckboxControl
										key={index}
										label={role.label}
										checked={conditions.conditionUserRole.properties.userRole.includes(role.value)}
										onChange={(isChecked) => {
											const newRoles = isChecked
												? [...conditions.conditionUserRole.properties.userRole, role.value]
												: conditions.conditionUserRole.properties.userRole.filter((r) => r !== role.value);
											updateConditions('conditionUserRole', { userRole: newRoles });
										}}
									/>
								))}
							</BaseControl>
						)}

						{conditions.conditionCustomField.enable && (
							<>
								<TextControl
									label={__('Custom Field Name', 'vk-dynamic-if-block')}
									value={conditions.conditionCustomField.properties.customFieldName}
									onChange={(value) => updateConditions('conditionCustomField', { ...conditions.conditionCustomField.properties, customFieldName: value })}
								/>
								{conditions.conditionCustomField.properties.customFieldName && (
									<>
										<SelectControl
											label={__('Custom Field Rule', 'vk-dynamic-if-block')}
											value={conditions.conditionCustomField.properties.customFieldRule}
											options={[
												{ value: 'valueExists', label: __('Value Exists', 'vk-dynamic-if-block') },
												{ value: 'valueEquals', label: __('Value Equals', 'vk-dynamic-if-block') },
											]}
											onChange={(value) => updateConditions('conditionCustomField', { ...conditions.conditionCustomField.properties, customFieldRule: value })}
										/>
										{conditions.conditionCustomField.properties.customFieldRule === 'valueEquals' && (
											<>
												<TextControl
													label={__('Custom Field Value', 'vk-dynamic-if-block')}
													value={conditions.conditionCustomField.properties.customFieldValue}
													onChange={(value) => updateConditions('conditionCustomField', { ...conditions.conditionCustomField.properties, customFieldValue: value })}
												/>
											</>
										)}
									</>
								)}
							</>
						)}

						{conditions.conditionPeriodDisplay.enable && (
							<>
								<SelectControl
									label={__('Display Period Setting', 'vk-dynamic-if-block')}
									value={conditions.conditionPeriodDisplay.properties.periodDisplaySetting}
									options={[
										{ value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
										{ value: 'deadline', label: __('Set to display deadline', 'vk-dynamic-if-block') },
										{ value: 'startline', label: __('Set to display startline', 'vk-dynamic-if-block') },
										{ value: 'daysSincePublic', label: __('Number of days from the date of publication', 'vk-dynamic-if-block') },
									]}
									onChange={(value) => updateConditions('conditionPeriodDisplay', { ...conditions.conditionPeriodDisplay.properties, periodDisplaySetting: value })}
									help={
										conditions.conditionPeriodDisplay.properties.periodDisplaySetting === 'deadline'
											? __('After the specified date, it is hidden.', 'vk-dynamic-if-block')
											: conditions.conditionPeriodDisplay.properties.periodDisplaySetting === 'startline'
												? __('After the specified date, it is display.', 'vk-dynamic-if-block')
												: conditions.conditionPeriodDisplay.properties.periodDisplaySetting === 'daysSincePublic'
													? __('After the specified number of days, it is hidden.', 'vk-dynamic-if-block')
													: __('You can set the deadline or startline to be displayed, as well as the time period.', 'vk-dynamic-if-block')
									}
								/>
								{conditions.conditionPeriodDisplay.properties.periodDisplaySetting !== 'none' && (
									<>
										<SelectControl
											label={__('Period specification method', 'vk-dynamic-if-block')}
											value={conditions.conditionPeriodDisplay.properties.periodSpecificationMethod}
											options={[
												{ value: 'direct', label: __('Direct input in this block', 'vk-dynamic-if-block') },
												{ value: 'referCustomField', label: __('Refer to value of custom field', 'vk-dynamic-if-block') },
											]}
											onChange={(value) => updateConditions('conditionPeriodDisplay', { ...conditions.conditionPeriodDisplay.properties, periodSpecificationMethod: value })}
										/>
										{conditions.conditionPeriodDisplay.properties.periodSpecificationMethod === 'direct' && (
											<NumberControl
												label={__('Value for the specified period', 'vk-dynamic-if-block')}
												type={conditions.conditionPeriodDisplay.properties.periodDisplaySetting === 'daysSincePublic' ? 'number' : 'datetime-local'}
												step={conditions.conditionPeriodDisplay.properties.periodDisplaySetting === 'daysSincePublic' ? 1 : 60}
												value={conditions.conditionPeriodDisplay.properties.periodDisplayValue}
												onChange={(value) => updateConditions('conditionPeriodDisplay', { ...conditions.conditionPeriodDisplay.properties, periodDisplayValue: value })}
											/>
										)}
										{conditions.conditionPeriodDisplay.properties.periodSpecificationMethod === 'referCustomField' && (
											<>
												<TextControl
													label={__('Referenced custom field name', 'vk-dynamic-if-block')}
													value={conditions.conditionPeriodDisplay.properties.periodReferCustomField}
													onChange={(value) => updateConditions('conditionPeriodDisplay', { ...conditions.conditionPeriodDisplay.properties, periodReferCustomField: value })}
													help={
														conditions.conditionPeriodDisplay.properties.periodDisplaySetting === 'daysSincePublic'
															? __('Save the value of the custom field as an integer.', 'vk-dynamic-if-block')
															: __('Save the custom field values as Y-m-d H:i:s.', 'vk-dynamic-if-block')
													}
													className="vkdif__refer-cf-name"
												/>
												{!conditions.conditionPeriodDisplay.properties.periodReferCustomField && (
													<div className="vkdif__alert vkdif__alert-warning">
														{__('Enter the name of the custom field you wish to reference.', 'vk-dynamic-if-block')}
													</div>
												)}
											</>
										)}
									</>
								)}
							</>
						)}
					</BaseControl>
					<ToggleControl
						label={__('Exclusion designation', 'vk-dynamic-if-block')}
						checked={exclusion}
						onChange={(checked) => setAttributes({ exclusion: checked })}
					/>
				</BaseControl>
			)}
		</>
	);
};

export default ControlsPanel;
