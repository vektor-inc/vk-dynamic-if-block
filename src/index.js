import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('vk-blocks/dynamic-if', {
  apiVersion: 2,
  title: __('Dynamic If', 'vk-dynamic-if-block'),
  category: 'layout',
  attributes: {
    displayCondition: {
      type: 'string',
      default: 'none',
    },
  },
  supports: {
    html: false,
    innerBlocks: true,
  },
  edit({ attributes, setAttributes }) {
    const { displayCondition } = attributes;

    const displayConditions = [
      { value: 'none', label: __('No restriction', 'vk-dynamic-if-block') },
      { value: 'is_front_page', label: __('Front Page', 'vk-dynamic-if-block') },
      { value: 'is_single', label: __('Single Post', 'vk-dynamic-if-block') },
    ];

    return (
      <div {...useBlockProps()}>
        <InspectorControls>
          <PanelBody title={__('Display Conditions', 'vk-dynamic-if-block')}>
            <SelectControl
              label={__('Select a condition', 'vk-dynamic-if-block')}
              value={displayCondition}
              options={displayConditions}
              onChange={(value) => setAttributes({ displayCondition: value })}
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
