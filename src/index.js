import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import blockAttributes from './block.json';

registerBlockType( blockAttributes.name, {
	title: __( 'Dynamic If', 'vk-dynamic-if-block' ),
	icon: 'admin-comments',
	category: 'layout',
	attributes: blockAttributes.attributes,

	edit: ( { attributes, setAttributes } ) => {
		const { displayCondition } = attributes;

		const onChangeDisplayCondition = ( newDisplayCondition ) => {
			setAttributes( { displayCondition: newDisplayCondition } );
		};

		return (
			<Fragment>
				<InspectorControls>
					<PanelBody title={ __( 'Display Condition', 'vk-dynamic-if-block' ) }>
						<SelectControl
							label={ __( 'Select condition', 'vk-dynamic-if-block' ) }
							value={ displayCondition }
							options={ [
								{ value: 'no-limit', label: __( 'No Limit', 'vk-dynamic-if-block' ) },
								{ value: 'is_front_page', label: __( 'Front Page', 'vk-dynamic-if-block' ) },
								{ value: 'is_single', label: __( 'Single', 'vk-dynamic-if-block' ) },
							] }
							onChange={ onChangeDisplayCondition }
						/>
					</PanelBody>
				</InspectorControls>
				<div className="vk-dynamic-if-block">
					<InnerBlocks />
				</div>
			</Fragment>
		);
	},

	save: () => {
		return <InnerBlocks.Content />;
	},
} );
