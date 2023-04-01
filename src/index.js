import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('vk-blocks/dynamic-if', {
  apiVersion: 2,
  title: __('Dynamic If', 'vk-dynamic-if-block'),
  category: 'layout',
  attributes: {
    pageType: {
      type: 'string',
      default: 'none',
    },
  },
  supports: {
    html: false,
    innerBlocks: true,
  },
  edit({ attributes, setAttributes }) {
    const { pageType } = attributes;

    const pageTypes = [
      { value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
      { value: 'is_front_page', label: __('Front Page', 'vk-dynamic-if-block') + ' : is_front_page()' },
      { value: 'is_single', label: __('Single', 'vk-dynamic-if-block') + ' : is_single()' },
      { value: 'is_page', label: __('Page', 'vk-dynamic-if-block') + ' : is_page()' },
      { value: 'is_singular', label: __('Singular', 'vk-dynamic-if-block') + ' : is_singular()' },
      { value: 'is_home', label: __('Post Top', 'vk-dynamic-if-block') + ' : is_home() && ! is_front_page()' },
      { value: 'is_archive', label: __('Archive', 'vk-dynamic-if-block') + ' : is_archive()' },
      { value: 'is_search', label: __('Search Result', 'vk-dynamic-if-block') + ' : is_search()' },
      { value: 'is_404', label: __('404', 'vk-dynamic-if-block') + ' : is_404()' },
    ];

    return (
      <div {...useBlockProps()}>
        <InspectorControls>
          <PanelBody title={__('Display Conditions', 'vk-dynamic-if-block')}>
            <SelectControl
              label={__('Select a Page Type', 'vk-dynamic-if-block')}
              value={pageType}
              options={pageTypes}
              onChange={(value) => setAttributes({ pageType: value })}
            />
          </PanelBody>
        </InspectorControls>
        <InnerBlocks />
      </div>
    );
  },

  save() {
    return <InnerBlocks.Content />;
  },
});
