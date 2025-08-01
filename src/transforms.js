/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * External dependencies
 */
import { compareVersions } from 'compare-versions';
import { generateId } from './constants';

// WP6.3以上か NOTE: WP6.2以下をサポートしなくなったら削除すること
const isLargerThanWp63 = () => {
	if (
		window.wpVersion !== undefined &&
		window.wpVersion !== null &&
		compareVersions( window.wpVersion, '6.3' ) < 0
	) {
		return false;
	}
	return true;
};

const transforms = {
	from: [
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ '*' ],
			__experimentalConvert( blocks ) {
				// Clone the Blocks to be Grouped
				// Failing to create new block references causes the original blocks
				// to be replaced in the switchToBlockType call thereby meaning they
				// are removed both from their original location and within the
				// new group block.
				const groupInnerBlocks = blocks.map( ( block ) => {
					return createBlock(
						block.name,
						block.attributes,
						block.innerBlocks
					);
				} );

				// 新しいIDを生成してブロックを作成
				const newAttributes = {
					conditions: [
						{
							id: generateId(),
							name: 'Condition 1',
							conditions: [],
							operator: 'and',
						},
					],
				};

				return createBlock(
					'vk-blocks/dynamic-if',
					newAttributes,
					groupInnerBlocks
				);
			},
		},
	],
	ungroup: isLargerThanWp63()
		? ( attributes, innerBlocks ) => innerBlocks
		: undefined,
	to: ! isLargerThanWp63()
		? [
				{
					type: 'block',
					blocks: [ '*' ],
					transform: ( attributes, innerBlocks ) => innerBlocks,
				},
		  ]
		: undefined,
};

export default transforms;
