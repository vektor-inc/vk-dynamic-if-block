import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	CheckboxControl,
	BaseControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
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
			type: 'string',
			default: 'none',
		},
		ifPostType: {
			type: 'string',
			default: 'none',
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
		displayPeriodSetting: {
			type: 'string',
			"default": "none"
		},
		periodSpecificationMethod: {
			type: 'string',
			"default": "direct"
		},
		displayPeriodValue: {
			type: 'string',
			"default": ""
		},
		referCustomFieldName: {
			type: 'string',
			"default": ""
		},
	},
	supports: {
		html: false,
		innerBlocks: true,
	},
	edit({ attributes, setAttributes }) {
		const { ifPageType, ifPostType, userRole, customFieldName, customFieldRule, customFieldValue, exclusion, displayPeriodSetting, periodSpecificationMethod, displayPeriodValue, referCustomFieldName } = attributes;

		const ifPageTypes = [
			{ value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
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

		if (ifPageType !== "none") {
			labels.push(ifPageType);
		}

		if (ifPostType !== "none") {
			labels.push(ifPostType);
		}

		if (customFieldName) {
			labels.push(customFieldName);
		}

		if (displayPeriodSetting !== "none") {
			labels.push(displayPeriodSetting);
		}

		let labels_string = labels.join(" / ");

		return (
			<div {...useBlockProps({ className: blockClassName })}>
				<InspectorControls>
					<PanelBody title={__('Display Conditions', 'vk-dynamic-if-block')}>
						<SelectControl
							label={__('Page Type', 'vk-dynamic-if-block')}
							value={ifPageType}
							options={ifPageTypes}
							onChange={(value) => setAttributes({ ifPageType: value })}
						/>
						<SelectControl
							label={__('Post Type', 'vk-dynamic-if-block')}
							value={ifPostType}
							options={vk_dynamic_if_block_localize_data.postTypeSelectOptions}
							onChange={(value) => setAttributes({ ifPostType: value })}
						/>
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
						<BaseControl title={__('Display Period', 'vk-dynamic-if-block')}>
							<SelectControl
								label={__('Display Period Setting', 'vk-dynamic-if-block')}
								value={displayPeriodSetting}
								options={[
									{ value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
									{ value: 'deadline', label: __('Set to display deadline', 'vk-dynamic-if-block') },
									{ value: 'startline', label: __('Set to display startline', 'vk-dynamic-if-block') },
									{ value: 'daysSincePublic', label: __('Number of days from the date of publication', 'vk-dynamic-if-block') },
								]}
								onChange={(value) => setAttributes({ displayPeriodSetting: value })}
								help={
									displayPeriodSetting === 'deadline'
										? __('After the specified date, it is hidden.', 'vk-dynamic-if-block')
										: displayPeriodSetting === 'startline'
											? __('After the specified date, it is display.', 'vk-dynamic-if-block')
											: displayPeriodSetting === 'daysSincePublic'
												? __('After the specified number of days, it is hidden.', 'vk-dynamic-if-block')
												: __('You can set the deadline or startline to be displayed, as well as the time period.', 'vk-dynamic-if-block')
								}
							/>
							{displayPeriodSetting !== 'none' && (
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
											type={displayPeriodSetting === 'daysSincePublic' ? 'number' : 'datetime-local'}
											step={displayPeriodSetting === 'daysSincePublic' ? 1 : 60}
											value={displayPeriodValue}
											onChange={(value) =>
												setAttributes({ displayPeriodValue: value })
											}
										/>
									)}
									{periodSpecificationMethod === 'referCustomField' && (
										<TextControl
											label={__('Referenced Custom Field Value', 'vk-dynamic-if-block')}
											value={referCustomFieldName}
											onChange={(value) =>
												setAttributes({ referCustomFieldName: value })
											}
											help={
												displayPeriodSetting === 'daysSincePublic'
													? __('Save the value of the custom field as an integer.', 'vk-dynamic-if-block')
													: __('Save the custom field values as Y-m-d H:i.', 'vk-dynamic-if-block')
											}
										/>
									)}
								</>
							)}

						</BaseControl>
						<ToggleControl
							label={__('Exclusion designation', 'vk-dynamic-if-block')}
							checked={exclusion}
							onChange={(checked) => setAttributes({ exclusion: checked })}
						/>
					</PanelBody>
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
