import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit(props) {
    const { attributes, setAttributes } = props;
    const { condition } = attributes;

    const onSelectCondition = (value) => {
        setAttributes({ condition: value });
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Condition', 'vk-dynamic-if-block')}>
                    <SelectControl
                        label={__('Condition', 'vk-dynamic-if-block')}
                        value={condition}
                        options={[
                            { value: 'is_front_page', label: __('is_front_page()', 'vk-dynamic-if-block') },
                            { value: 'is_single', label: __('is_single()', 'vk-dynamic-if-block') },
                        ]}
                        onChange={onSelectCondition}
                    />
                </PanelBody>
            </InspectorControls>
            <InnerBlocks />
        </>
    );
}
