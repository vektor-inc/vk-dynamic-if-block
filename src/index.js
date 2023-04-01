import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import './editor.scss';

const blockStyle = {
    backgroundColor: 'transparent',
    color: 'inherit',
    padding: '1em',
};

registerBlockType('vk-blocks/dynamic-if', {
    apiVersion: 2,
    title: __('Dynamic If', 'vk-dynamic-if-block'),
    icon: 'visibility',
    category: 'layout',
    supports: {
        html: false,
    },
    attributes: {
        condition: {
            type: 'string',
            default: 'none',
        },
    },
    edit: ({ attributes, setAttributes }) => {
        const { condition } = attributes;

        return (
            <div style={blockStyle}>
                <InspectorControls>
                    <PanelBody title={__('Display Condition', 'vk-dynamic-if-block')}>
                        <SelectControl
                            label={__('Condition', 'vk-dynamic-if-block')}
                            value={condition}
                            options={[
                                { value: 'none', label: __('None (always display)', 'vk-dynamic-if-block') },
                                { value: 'if_front_page', label: 'if_front_page()' },
                                { value: 'is_single', label: 'is_single()' },
                            ]}
                            onChange={(selected) => setAttributes({ condition: selected })}
                        />
                    </PanelBody>
                </InspectorControls>
                <InnerBlocks />
            </div>
        );
    },
    save: ({ attributes }) => {
        const { condition } = attributes;

        return (
            <div data-display-condition={condition}>
                <InnerBlocks.Content />
            </div>
        );
    },
});
