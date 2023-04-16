import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { ReactComponent as Icon } from './icon.svg';

registerBlockType('vk-blocks/dynamic-if', {
	apiVersion: 2,
	title: __('Dynamic If', 'vk-dynamic-if-block'),
	icon: <Icon />,
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
	},
	supports: {
		html: false,
		innerBlocks: true,
	},
	edit({ attributes, setAttributes }) {
		const { ifPageType, ifPostType } = attributes;

		const ifPageTypes = [
			{ value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
			{ value: 'is_front_page', label: __('Front Page', 'vk-dynamic-if-block') + ' ( is_front_page() )' },
			{ value: 'is_single', label: __('Single', 'vk-dynamic-if-block') + ' ( is_single() )' },
			{ value: 'is_page', label: __('Page', 'vk-dynamic-if-block') + ' ( is_page() )' },
			{ value: 'is_singular', label: __('Singular', 'vk-dynamic-if-block') + ' ( is_singular() )' },
			{ value: 'is_home', label: __('Post Top', 'vk-dynamic-if-block') + ' ( is_home() && ! is_front_page() )' },
			{ value: 'is_archive', label: __('Archive', 'vk-dynamic-if-block') + ' ( is_archive() )' },
			{ value: 'is_search', label: __('Search Result', 'vk-dynamic-if-block') + ' ( is_search() )' },
			{ value: 'is_404', label: __('404', 'vk-dynamic-if-block') + ' ( is_404() )' },
		];

		const blockClassName = `vk-dynamic-if-block ifPageType-${ifPageType} ifPostType-${ifPostType}`;

		return (
			<div {...useBlockProps({ className: blockClassName })}>
				<InspectorControls>
					<PanelBody title={__('Display Conditions', 'vk-dynamic-if-block')}>
						<SelectControl
							label={__('Select a Page Type', 'vk-dynamic-if-block')}
							value={ifPageType}
							options={ifPageTypes}
							onChange={(value) => setAttributes({ ifPageType: value })}
						/>
						<SelectControl
							label={__('Select a Post Type', 'vk-dynamic-if-block')}
							value={ifPostType}
							options={vk_dynamic_if_block_localize_data.postTypeSelectOptions}
							onChange={(value) => setAttributes({ ifPostType: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<div className="vk-dynamic-if-block__label">{ifPageType} / {ifPostType}</div>
				<InnerBlocks />
			</div>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},
});