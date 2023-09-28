import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { ReactComponent as Icon } from './icon.svg';
import transforms from './transforms';
import ControlsPanel from './ControlsPanel'

registerBlockType('vk-blocks/dynamic-if', {
	apiVersion: 2,
	title: __('Dynamic If', 'vk-dynamic-if-block'),
	icon: <Icon />,
	transforms,
	category: 'theme',
	attributes: {
		conditions: {
			type: "object",
			default: {
				conditionPageType: {
					enable: false,
					properties: {
						ifPageType: "none",
					}
				},
				conditionPostType: {
					enable: false,
					properties: {
						ifPostType: "none",
					}
				},
				conditionUserRole: {
					enable: false,
					properties: {
						userRole: [],
					}
				},
				conditionCustomField: {
					enable: false,
					properties: {
						customFieldName: "",
						customFieldRule: "valueExists",
						customFieldValue: "",
					}
				},
				conditionPeriodDisplay: {
					enable: false,
					properties: {
						periodDisplaySetting: "none",
						periodSpecificationMethod: "direct",
						periodDisplayValue: "",
						periodReferCustomField: "",
					}
				},
			}
		},
		exclusion: {
			type: "boolean",
			default: false,
		},
	},
	supports: {
		html: false,
		innerBlocks: true,
	},
	edit({ attributes, setAttributes }) {
		const {
			conditions: {
				conditionPageType: { enable: enablePageType, properties: { ifPageType } },
				conditionPostType: { enable: enablePostType, properties: { ifPostType } },
				conditionUserRole: { enable: enableUserRole, properties: { userRole } },
				conditionCustomField: { enable: enableCustomField, properties: { customFieldName } },
				conditionPeriodDisplay: { enable: enablePeriodDisplay, properties: { periodDisplaySetting } },
			},
		} = attributes;

		const blockClassName = `vk-dynamic-if-block ifPageType-${ifPageType} ifPostType-${ifPostType}`;
		const MY_TEMPLATE = [
			['core/paragraph', {}],
		];

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

		const userRolesObj = vk_dynamic_if_block_localize_data.userRoles || {};
		const userRoles = Object.keys(userRolesObj).map(key => ({
			value: key,
			label: __(userRolesObj[key], 'vk-dynamic-if-block'),
		}));

		let labels = [];

		if (enablePageType && ifPageType !== "none") {
			labels.push(ifPageType);
		}
		if (enablePostType && ifPostType !== "none") {
			labels.push(ifPostType);
		}
		if (enableUserRole && userRole.length > 0) {
			userRole.forEach((roleValue) => {
				const roleLabel = userRoles.find((role) => role.value === roleValue);
				if (roleLabel) {
					labels.push(roleLabel.label);
				}
			});
		}
		if (enableCustomField && customFieldName) {
			labels.push(customFieldName);
		}
		if (enablePeriodDisplay && periodDisplaySetting !== "none") {
			labels.push(periodDisplaySetting);
		}

		let labels_string = labels.join(" / ");

		return (
			<div {...useBlockProps({ className: blockClassName })}>
				<InspectorControls group="settings">
					<div className="vkdif__controls-panel">
						<ControlsPanel attributes={attributes} setAttributes={setAttributes} ifPageTypes={ifPageTypes} userRoles={userRoles} />
					</div>
					{/* <ToggleControl
						label={__('Exclusion designation', 'vk-dynamic-if-block')}
						checked={exclusion}
						onChange={(checked) => setAttributes({ exclusion: checked })}
					/> */}
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
