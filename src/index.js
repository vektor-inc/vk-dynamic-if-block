import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import './editor.scss';

registerBlockType('vk-blocks/dynamic-if', {
  apiVersion: 2,

  edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({
      className: 'vk-dynamic-if-block',
    });

    const onChangeCondition = (value) => {
      setAttributes({ condition: value });
    };

    return (
      <div {...blockProps}>
        <SelectControl
          label={__('Display condition', 'vk-dynamic-if-block')}
          value={attributes.condition}
          options={[
            { label: __('Front page', 'vk-dynamic-if-block'), value: 'is_front_page' },
            { label: __('Single post', 'vk-dynamic-if-block'), value: 'is_single' },
          ]}
          onChange={onChangeCondition}
        />
        <InnerBlocks />
      </div>
    );
  },

  save() {
    return <InnerBlocks.Content />;
  },
});
