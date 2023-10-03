import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import {
	Panel,
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	CheckboxControl,
	BaseControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { ReactComponent as Icon } from './icon.svg';
import transforms from './transforms';

registerBlockType('vk-blocks/dynamic-if', {
	apiVersion: 2,
	title: __('Dynamic If', 'vk-dynamic-if-block'),
	icon: <Icon />,
	transforms,
	category: 'theme',
	attributes: {
		ifPageType: {
			type: 'array',
			default: [],
		},
		ifPostType: {
			type: 'array',
			default: [],
		},
		userRole: {
			type: 'array',
			default: [],
		},
		customFieldName: {
			type: 'string',
			"default": ""
		},
		customFieldRule: {
			type: 'string',
			"default": ""
		},
		customFieldValue: {
			type: 'string',
			"default": ""
		},
		exclusion: {
			type: 'boolian',
			default: false,
		},
		periodDisplaySetting: {
			type: 'string',
			"default": "none"
		},
		periodSpecificationMethod: {
			type: 'string',
			"default": "direct"
		},
		periodDisplayValue: {
			type: 'string',
			"default": ""
		},
		periodReferCustomField: {
			type: 'string',
			"default": ""
		},
	},
	supports: {
		html: false,
		innerBlocks: true,
	},
	edit({ attributes, setAttributes }) {
		const { ifPageType, ifPostType, userRole, customFieldName, customFieldRule, customFieldValue, exclusion, periodDisplaySetting, periodSpecificationMethod, periodDisplayValue, periodReferCustomField } = attributes;

		const ifPageTypes = [
			{ value: 'is_front_page', label: __('Front Page', 'vk-dynamic-if-block') + ' ( is_front_page() )' },
			{ value: 'is_single', label: __('Single', 'vk-dynamic-if-block') + ' ( is_single() )' },
			{ value: 'is_page', label: __('Page', 'vk-dynamic-if-block') + ' ( is_page() )' },
			{ value: 'is_singular', label: __('Singular', 'vk-dynamic-if-block') + ' ( is_singular() )' },
			{ value: 'is_home', label: __('Post Top', 'vk-dynamic-if-block') + ' ( is_home() && ! is_front_page() )' },
			{ value: 'is_post_type_archive', label: __('Post Type Archive', 'vk-dynamic-if-block') + ' ( is_post_type_archive() )' },
			{ value: 'is_category', label: __('Category Archive', 'vk-dynamic-if-block') + ' ( is_category() )' },
			{ value: 'is_tag', label: __('Tag Archive', 'vk-dynamic-if-block') + ' ( is_tag() )' },
			{ value: 'is_tax', label: __('Taxonomy Archive', 'vk-dynamic-if-block') + ' ( is_tax() )' },
			{ value: 'is_year', label: __('Yearly Archive', 'vk-dynamic-if-block') + ' ( is_year() )' },
			{ value: 'is_month', label: __('Monthly Archive', 'vk-dynamic-if-block') + ' ( is_month() )' },
			{ value: 'is_date', label: __('Daily Archive', 'vk-dynamic-if-block') + ' ( is_date() )' },
			{ value: 'is_author', label: __('Author Archive', 'vk-dynamic-if-block') + ' ( is_author() )' },
			{ value: 'is_archive', label: __('Archive', 'vk-dynamic-if-block') + ' ( is_archive() )' },
			{ value: 'is_search', label: __('Search Result', 'vk-dynamic-if-block') + ' ( is_search() )' },
			{ value: 'is_404', label: __('404', 'vk-dynamic-if-block') + ' ( is_404() )' },
		];

		const blockClassName = `vk-dynamic-if-block ifPageType-${ifPageType} ifPostType-${ifPostType}`;
		const MY_TEMPLATE = [
			['core/paragraph', {}],
		];

		const userRolesObj = vk_dynamic_if_block_localize_data.userRoles || {};
		const userRoles = Object.keys(userRolesObj).map(key => ({
			value: key,
			label: __(userRolesObj[key], 'vk-dynamic-if-block'),
		}));

		let labels = [];

		if (ifPageType.length > 0) {
			ifPageType.forEach((pageTypeValue) => {
				const pageTypeLabel = ifPageTypes.find((pageType) => pageType.value === pageTypeValue);
				if (pageTypeLabel) {
					labels.push(pageTypeLabel.value);
				}
			});
		}
		if (ifPostType.length > 0) {
			ifPostType.forEach((postTypeValue) => {
				const postTypeLabel = vk_dynamic_if_block_localize_data.postTypeSelectOptions.find((postType) => postType.value === postTypeValue);
				if (postTypeLabel) {
					labels.push(postTypeLabel.value);
				}
			});
		}
		if (userRole.length > 0) {
			userRole.forEach((roleValue) => {
				const roleLabel = userRoles.find((role) => role.value === roleValue);
				if (roleLabel) {
					labels.push(roleLabel.label);
				}
			});
		}

		if (customFieldName) {
			labels.push(customFieldName);
		}

		if (periodDisplaySetting !== "none") {
			labels.push(periodDisplaySetting);
		}

		let labels_string = labels.join(" / ");

		return (
			<div {...useBlockProps({ className: blockClassName })}>
				<InspectorControls>
					<Panel header={__('Display Conditions', 'vk-dynamic-if-block')} className={'vkdif'}>
						<PanelBody title={__('Page Type', 'vk-dynamic-if-block')} initialOpen={false}>
							<BaseControl
								__nextHasNoMarginBottom
								className="dynamic-if-user-role"
								label={__('Page Type', 'vk-dynamic-if-block')}
								help={__('If unchecked, no restrictions are imposed by page type', 'vk-dynamic-if-block')}
							>
								{ifPageTypes.map((pageType, index) => {
									return (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={index}
											label={pageType.label}
											checked={ifPageType.includes(pageType.value)}
											onChange={(isChecked) => {
												if (isChecked) {
													setAttributes({ ifPageType: [...ifPageType, pageType.value] });
												} else {
													setAttributes({ ifPageType: ifPageType.filter((v) => v !== pageType.value) });
												}
											}}
										/>
									);
								})}
							</BaseControl>
						</PanelBody>
						<PanelBody title={__('Post Type', 'vk-dynamic-if-block')} initialOpen={false}>
							<BaseControl
								__nextHasNoMarginBottom
								className="dynamic-if-user-role"
								label={__('Post Type', 'vk-dynamic-if-block')}
								help={__('If unchecked, no restrictions are imposed by post type', 'vk-dynamic-if-block')}
							>
								{vk_dynamic_if_block_localize_data.postTypeSelectOptions.map((postType, index) => {
									return (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={index}
											label={postType.label}
											checked={ifPostType.includes(postType.value)}
											onChange={(isChecked) => {
												if (isChecked) {
													setAttributes({ ifPostType: [...ifPostType, postType.value] });
												} else {
													setAttributes({ ifPostType: ifPostType.filter((v) => v !== postType.value) });
												}
											}}
										/>
									);
								})}
							</BaseControl>
						</PanelBody>
						<PanelBody title={__('User Role', 'vk-dynamic-if-block')} initialOpen={false}>
							<BaseControl
								__nextHasNoMarginBottom
								className="dynamic-if-user-role"
								label={__('User Role', 'vk-dynamic-if-block')}
								help={__('If unchecked, no restrictions are imposed by user role', 'vk-dynamic-if-block')}
							>
								{userRoles.map((role, index) => {
									return (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={index}
											label={role.label}
											checked={userRole.includes(role.value)}
											onChange={(isChecked) => {
												if (isChecked) {
													setAttributes({ userRole: [...userRole, role.value] });
												} else {
													setAttributes({ userRole: userRole.filter((r) => r !== role.value) });
												}
											}}
										/>
									);
								})}
							</BaseControl>
						</PanelBody>
						<PanelBody title={__('Custom Field Name', 'vk-dynamic-if-block')} initialOpen={false}>
							<TextControl
								label={__('Custom Field Name', 'vk-dynamic-if-block')}
								value={customFieldName}
								onChange={(value) =>
									setAttributes({ customFieldName: value })
								}
							/>
							{customFieldName && (
								<>
									<SelectControl
										label={__('Custom Field Rule', 'vk-dynamic-if-block')}
										value={customFieldRule}
										options={[
											{ value: 'valueExists', label: __('Value Exist ( !empty() )', 'vk-dynamic-if-block') },
											{ value: 'valueEquals', label: __('Value Equals ( === )', 'vk-dynamic-if-block') },
										]}
										onChange={(value) => setAttributes({ customFieldRule: value })}
									/>
									{customFieldRule === 'valueEquals' && (
										<>
											<TextControl
												label={__('Custom Field Value', 'vk-dynamic-if-block')}
												value={customFieldValue}
												onChange={(value) =>
													setAttributes({ customFieldValue: value })
												}
											/>
										</>
									)}
								</>
							)}
						</PanelBody>
						<PanelBody title={__('Display Period', 'vk-dynamic-if-block')} initialOpen={false}>
							<BaseControl title={__('Display Period', 'vk-dynamic-if-block')}>
								<SelectControl
									label={__('Display Period Setting', 'vk-dynamic-if-block')}
									value={periodDisplaySetting}
									options={[
										{ value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
										{ value: 'deadline', label: __('Set to display deadline', 'vk-dynamic-if-block') },
										{ value: 'startline', label: __('Set to display startline', 'vk-dynamic-if-block') },
										{ value: 'daysSincePublic', label: __('Number of days from the date of publication', 'vk-dynamic-if-block') },
									]}
									onChange={(value) => setAttributes({ periodDisplaySetting: value })}
									help={
										periodDisplaySetting === 'deadline'
											? __('After the specified date, it is hidden.', 'vk-dynamic-if-block')
											: periodDisplaySetting === 'startline'
												? __('After the specified date, it is display.', 'vk-dynamic-if-block')
												: periodDisplaySetting === 'daysSincePublic'
													? __('After the specified number of days, it is hidden.', 'vk-dynamic-if-block')
													: __('You can set the deadline or startline to be displayed, as well as the time period.', 'vk-dynamic-if-block')
									}
								/>
								{periodDisplaySetting !== 'none' && (
									<>
										<SelectControl
											label={__('Period specification method', 'vk-dynamic-if-block')}
											value={periodSpecificationMethod}
											options={[
												{ value: 'direct', label: __('Direct input in this block', 'vk-dynamic-if-block') },
												{ value: 'referCustomField', label: __('Refer to value of custom field', 'vk-dynamic-if-block') },
											]}
											onChange={(value) => setAttributes({ periodSpecificationMethod: value })}
										/>
										{periodSpecificationMethod === 'direct' && (
											<NumberControl
												label={__('Value for the specified period', 'vk-dynamic-if-block')}
												type={periodDisplaySetting === 'daysSincePublic' ? 'number' : 'datetime-local'}
												step={periodDisplaySetting === 'daysSincePublic' ? 1 : 60}
												value={periodDisplayValue}
												onChange={(value) =>
													setAttributes({ periodDisplayValue: value })
												}
											/>
										)}
										{periodSpecificationMethod === 'referCustomField' && (
											<>
												<TextControl
													label={__('Referenced custom field name', 'vk-dynamic-if-block')}
													value={periodReferCustomField}
													onChange={(value) =>
														setAttributes({ periodReferCustomField: value })
													}
													help={
														periodDisplaySetting === 'daysSincePublic'
															? __('Save the value of the custom field as an integer.', 'vk-dynamic-if-block')
															: __('Save the custom field values as Y-m-d H:i:s.', 'vk-dynamic-if-block')
													}
													className="vkdif__refer-cf-name"
												/>
												{!periodReferCustomField && (
													<div className="vkdif__alert vkdif__alert-warning">
														{__('Enter the name of the custom field you wish to reference.', 'vk-dynamic-if-block')}
													</div>
												)}
											</>
										)}
									</>
								)}
							</BaseControl>
						</PanelBody>
						<PanelBody title={__('Exclusion designation', 'vk-dynamic-if-block')} initialOpen={false}>
							<ToggleControl
								label={__('Exclusion designation', 'vk-dynamic-if-block')}
								checked={exclusion}
								onChange={(checked) => setAttributes({ exclusion: checked })}
							/>
						</PanelBody>
					</Panel>
				</InspectorControls>
				<div className="vk-dynamic-if-block__label">{labels_string}</div>

				<InnerBlocks
					template={MY_TEMPLATE}
				/>
			</div>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},
});
