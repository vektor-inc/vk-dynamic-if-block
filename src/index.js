import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	CheckboxControl,
	BaseControl,
	Button,
	__experimentalNumberControl as NumberControl,
	Tooltip,
} from '@wordpress/components';
import { ReactComponent as Icon } from './icon.svg';
import transforms from './transforms';
import React, { useState } from 'react';

registerBlockType( 'vk-blocks/dynamic-if', {
	apiVersion: 3,
	title: __( 'Dynamic If', 'vk-dynamic-if-block' ),
	icon: <Icon />,
	transforms,
	category: 'theme',
	attributes: {
		conditions: {
			type: 'array',
			default: [
				{
					id: 'default-group',
					name: 'Group 1',
					conditions: [],
					operator: 'and'
				}
			],
		},
		conditionOperator: {
			type: 'string',
			default: 'and',
		},
		exclusion: {
			type: 'boolean',
			default: false,
		},
	},
	supports: {
		html: false,
		innerBlocks: true,
	},
	edit( { attributes, setAttributes } ) {
		const { conditions, conditionOperator, exclusion } = attributes;
		const [ showTooltip, setShowTooltip ] = useState( false );
		const [ tooltipContent, setTooltipContent ] = useState( '' );

		// 既存のブロックとの互換性のための移行処理
		React.useEffect( () => {
			if ( !conditions || conditions.length === 0 || (conditions[0] && conditions[0].conditions.length === 0) ) {
				const newConditions = [
					{
						id: 'default-group',
						name: 'Group 1',
						conditions: [
							{
								id: Date.now(),
								type: 'pageType',
								values: {},
							},
						],
						operator: 'and',
					},
				];
				setAttributes( { conditions: newConditions } );
			}
		}, [] );

		const migrateOldAttributes = ( oldAttributes ) => {
			const migratedConditions = [];

			// ページタイプの移行
			if ( oldAttributes.ifPageType && oldAttributes.ifPageType !== 'none' ) {
				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'pageType',
					values: {
						ifPageType: [ oldAttributes.ifPageType ],
					},
				} );
			}

			// 投稿タイプの移行
			if ( oldAttributes.ifPostType && oldAttributes.ifPostType !== 'none' ) {
				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'postType',
					values: {
						ifPostType: [ oldAttributes.ifPostType ],
					},
				} );
			}

			// 言語の移行
			if ( oldAttributes.ifLanguage && oldAttributes.ifLanguage !== 'none' ) {
				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'language',
					values: {
						ifLanguage: [ oldAttributes.ifLanguage ],
					},
				} );
			}

			// ユーザー権限の移行
			if ( oldAttributes.userRole && oldAttributes.userRole.length > 0 ) {
				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'userRole',
					values: {
						userRole: oldAttributes.userRole,
					},
				} );
			}

			// 投稿者の移行
			if ( oldAttributes.postAuthor && oldAttributes.postAuthor > 0 ) {
				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'postAuthor',
					values: {
						postAuthor: [ oldAttributes.postAuthor ],
					},
				} );
			}

			// カスタムフィールドの移行
			if ( oldAttributes.customFieldName ) {
				const customFieldValues = {
					customFieldName: oldAttributes.customFieldName,
				};
				
				if ( oldAttributes.customFieldRule ) {
					customFieldValues.customFieldRule = oldAttributes.customFieldRule;
				}
				
				if ( oldAttributes.customFieldValue ) {
					customFieldValues.customFieldValue = oldAttributes.customFieldValue;
				}

				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'customField',
					values: customFieldValues,
				} );
			}

			// 表示期間の移行
			if ( oldAttributes.periodDisplaySetting && oldAttributes.periodDisplaySetting !== 'none' ) {
				const periodValues = {
					periodDisplaySetting: oldAttributes.periodDisplaySetting,
				};
				
				if ( oldAttributes.periodSpecificationMethod ) {
					periodValues.periodSpecificationMethod = oldAttributes.periodSpecificationMethod;
				}
				
				if ( oldAttributes.periodDisplayValue ) {
					periodValues.periodDisplayValue = oldAttributes.periodDisplayValue;
				}
				
				if ( oldAttributes.periodReferCustomField ) {
					periodValues.periodReferCustomField = oldAttributes.periodReferCustomField;
				}

				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'period',
					values: periodValues,
				} );
			}

			// ログインユーザーの移行
			if ( oldAttributes.showOnlyLoginUser ) {
				migratedConditions.push( {
					id: Date.now() + Math.random(),
					type: 'loginUser',
					values: {
						showOnlyLoginUser: oldAttributes.showOnlyLoginUser,
					},
				} );
			}

			// 移行された条件がある場合はグループとして返す
			if ( migratedConditions.length > 0 ) {
				return [ {
					id: 'migrated-group',
					name: 'Group 1',
					conditions: migratedConditions,
					operator: 'and'
				} ];
			}

			return [];
		};

		const conditionTypes = [
			{
				value: 'pageType',
				label: __( 'Page Type', 'vk-dynamic-if-block' ),
			},
			{
				value: 'postType',
				label: __( 'Post Type', 'vk-dynamic-if-block' ),
			},
			{
				value: 'language',
				label: __( 'Language', 'vk-dynamic-if-block' ),
			},
			{
				value: 'userRole',
				label: __( 'User Role', 'vk-dynamic-if-block' ),
			},
			{
				value: 'postAuthor',
				label: __( 'Post Author', 'vk-dynamic-if-block' ),
			},
			{
				value: 'customField',
				label: __( 'Custom Field', 'vk-dynamic-if-block' ),
			},
			{
				value: 'period',
				label: __( 'Display Period', 'vk-dynamic-if-block' ),
			},
			{
				value: 'loginUser',
				label: __( 'Login User Only', 'vk-dynamic-if-block' ),
			},
		];

		const ifPageTypes = [
			{
				value: 'none',
				label: __( 'No restriction', 'vk-dynamic-if-block' ),
			},
			{
				value: 'is_front_page',
				label:
					__( 'Front Page', 'vk-dynamic-if-block' ) +
					' ( is_front_page() )',
			},
			{
				value: 'is_single',
				label:
					__( 'Single', 'vk-dynamic-if-block' ) + ' ( is_single() )',
			},
			{
				value: 'is_page',
				label: __( 'Page', 'vk-dynamic-if-block' ) + ' ( is_page() )',
			},
			{
				value: 'is_singular',
				label:
					__( 'Singular', 'vk-dynamic-if-block' ) +
					' ( is_singular() )',
			},
			{
				value: 'is_home',
				label:
					__( 'Post Top', 'vk-dynamic-if-block' ) +
					' ( is_home() && ! is_front_page() )',
			},
			{
				value: 'is_post_type_archive',
				label:
					__( 'Post Type Archive', 'vk-dynamic-if-block' ) +
					' ( is_post_type_archive() )',
			},
			{
				value: 'is_category',
				label:
					__( 'Category Archive', 'vk-dynamic-if-block' ) +
					' ( is_category() )',
			},
			{
				value: 'is_tag',
				label:
					__( 'Tag Archive', 'vk-dynamic-if-block' ) +
					' ( is_tag() )',
			},
			{
				value: 'is_tax',
				label:
					__( 'Taxonomy Archive', 'vk-dynamic-if-block' ) +
					' ( is_tax() )',
			},
			{
				value: 'is_year',
				label:
					__( 'Yearly Archive', 'vk-dynamic-if-block' ) +
					' ( is_year() )',
			},
			{
				value: 'is_month',
				label:
					__( 'Monthly Archive', 'vk-dynamic-if-block' ) +
					' ( is_month() )',
			},
			{
				value: 'is_date',
				label:
					__( 'Daily Archive', 'vk-dynamic-if-block' ) +
					' ( is_date() )',
			},
			{
				value: 'is_author',
				label:
					__( 'Author Archive', 'vk-dynamic-if-block' ) +
					' ( is_author() )',
			},
			{
				value: 'is_archive',
				label:
					__( 'Archive', 'vk-dynamic-if-block' ) +
					' ( is_archive() )',
			},
			{
				value: 'is_search',
				label:
					__( 'Search Result', 'vk-dynamic-if-block' ) +
					' ( is_search() )',
			},
			{
				value: 'is_404',
				label: __( '404', 'vk-dynamic-if-block' ) + ' ( is_404() )',
			},
		];

		const userRolesObj = vk_dynamic_if_block_localize_data.userRoles || {};
		const userRoles = Object.keys( userRolesObj ).map( ( key ) => ( {
			value: key,
			label: __( userRolesObj[ key ], 'vk-dynamic-if-block' ),
		} ) );

		const userSelectOptions = vk_dynamic_if_block_localize_data.userSelectOptions || [];

		const addCondition = () => {
			const newCondition = {
				id: Date.now(),
				type: 'pageType',
				values: {},
			};

			// 最初のグループに条件を追加
			const newConditions = [ ...conditions ];
			if ( newConditions.length === 0 ) {
				newConditions.push( {
					id: 'default-group',
					name: 'Group 1',
					conditions: [ newCondition ],
					operator: 'and'
				} );
			} else {
				newConditions[0].conditions.push( newCondition );
			}

			setAttributes( { conditions: newConditions } );
		};

		const addGroup = () => {
			// すでに使われているCondition Typeを取得
			const usedTypes = conditions.map(g => g.conditions[0]?.type).filter(Boolean);
			// 未使用のCondition Typeを取得
			const availableTypes = conditionTypes.map(opt => opt.value).filter(val => !usedTypes.includes(val));
			// 最初の未使用タイプ、なければ'pageType'をデフォルト
			const firstType = availableTypes[0] || 'pageType';
			const newGroup = {
				id: Date.now(),
				name: `Condition ${ conditions.length + 1 }`,
				conditions: [
					{
						id: Date.now(),
						type: firstType,
						values: {},
					},
				],
				operator: 'or',
			};
			setAttributes( { conditions: [ ...conditions, newGroup ] } );
		};

		const removeCondition = ( groupIndex, conditionIndex ) => {
			const newConditions = [ ...conditions ];
			newConditions[ groupIndex ].conditions.splice( conditionIndex, 1 );
			// グループに条件がなくなった場合はグループごと削除
			if ( newConditions[ groupIndex ].conditions.length === 0 ) {
				newConditions.splice( groupIndex, 1 );
			}
			setAttributes( { conditions: newConditions } );
		};

		const updateCondition = ( groupIndex, conditionIndex, updates ) => {
			const newConditions = [ ...conditions ];
			newConditions[ groupIndex ].conditions[ conditionIndex ] = { 
				...newConditions[ groupIndex ].conditions[ conditionIndex ], 
				...updates 
			};
			setAttributes( { conditions: newConditions } );
		};

		const updateConditionValue = ( groupIndex, conditionIndex, key, value ) => {
			const newConditions = [ ...conditions ];
			newConditions[ groupIndex ].conditions[ conditionIndex ].values = {
				...newConditions[ groupIndex ].conditions[ conditionIndex ].values,
				[ key ]: value,
			};
			setAttributes( { conditions: newConditions } );
		};

		const updateGroup = ( groupIndex, updates ) => {
			const newConditions = [ ...conditions ];
			newConditions[ groupIndex ] = { ...newConditions[ groupIndex ], ...updates };
			setAttributes( { conditions: newConditions } );
		};

		const renderConditionSettings = ( condition, groupIndex, conditionIndex ) => {
			const { type, values } = condition;

			switch ( type ) {
				case 'pageType':
		return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-page-type"
							label={ __( 'Page Type', 'vk-dynamic-if-block' ) }
							help={ __(
								'If unchecked, no restrictions are imposed by page type',
								'vk-dynamic-if-block'
							) }
						>
							{ ifPageTypes.map( ( pageType, pageTypeIndex ) => {
								const selectedPageTypes = values.ifPageType || [];
								return (
									<CheckboxControl
										__nextHasNoMarginBottom
										key={ pageTypeIndex }
										label={ pageType.label }
										checked={ selectedPageTypes.includes( pageType.value ) }
										onChange={ ( isChecked ) => {
											const newPageTypes = isChecked
												? [ ...selectedPageTypes, pageType.value ]
												: selectedPageTypes.filter( ( p ) => p !== pageType.value );
											updateConditionValue( groupIndex, conditionIndex, 'ifPageType', newPageTypes );
										} }
									/>
								);
							} ) }
						</BaseControl>
					);

				case 'postType':
					return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-post-type"
							label={ __( 'Post Type', 'vk-dynamic-if-block' ) }
							help={ __(
								'If unchecked, no restrictions are imposed by post type',
								'vk-dynamic-if-block'
							) }
						>
							{ vk_dynamic_if_block_localize_data.postTypeSelectOptions.map( ( postType, postTypeIndex ) => {
								const selectedPostTypes = values.ifPostType || [];
								return (
									<CheckboxControl
										__nextHasNoMarginBottom
										key={ postTypeIndex }
										label={ postType.label }
										checked={ selectedPostTypes.includes( postType.value ) }
										onChange={ ( isChecked ) => {
											const newPostTypes = isChecked
												? [ ...selectedPostTypes, postType.value ]
												: selectedPostTypes.filter( ( p ) => p !== postType.value );
											updateConditionValue( groupIndex, conditionIndex, 'ifPostType', newPostTypes );
										} }
									/>
								);
							} ) }
						</BaseControl>
					);

				case 'language':
					return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-language"
							label={ __( 'Language', 'vk-dynamic-if-block' ) }
							help={ __(
								'If unchecked, no restrictions are imposed by language',
								'vk-dynamic-if-block'
							) }
						>
							{ vk_dynamic_if_block_localize_data.languageSelectOptions.map( ( language, languageIndex ) => {
								const selectedLanguages = values.ifLanguage || [];
								return (
									<CheckboxControl
										__nextHasNoMarginBottom
										key={ languageIndex }
										label={ language.label }
										checked={ selectedLanguages.includes( language.value ) }
										onChange={ ( isChecked ) => {
											const newLanguages = isChecked
												? [ ...selectedLanguages, language.value ]
												: selectedLanguages.filter( ( l ) => l !== language.value );
											updateConditionValue( groupIndex, conditionIndex, 'ifLanguage', newLanguages );
										} }
									/>
								);
							} ) }
						</BaseControl>
					);

				case 'userRole':
					return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-user-role"
							label={ __( 'User Role', 'vk-dynamic-if-block' ) }
							help={ __(
								'If unchecked, no restrictions are imposed by user role',
								'vk-dynamic-if-block'
							) }
						>
							{ userRoles.map( ( role, roleIndex ) => {
								const selectedRoles = values.userRole || [];
								return (
									<CheckboxControl
										__nextHasNoMarginBottom
										key={ roleIndex }
										label={ role.label }
										checked={ selectedRoles.includes( role.value ) }
										onChange={ ( isChecked ) => {
											const newRoles = isChecked
												? [ ...selectedRoles, role.value ]
												: selectedRoles.filter( ( r ) => r !== role.value );
											updateConditionValue( groupIndex, conditionIndex, 'userRole', newRoles );
										} }
									/>
								);
							} ) }
						</BaseControl>
					);

				case 'postAuthor':
					return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-post-author"
							label={ __( 'Author', 'vk-dynamic-if-block' ) }
							help={ __(
								'If unchecked, no restrictions are imposed by author',
								'vk-dynamic-if-block'
							) }
						>
							{ userSelectOptions.map( ( user, userIndex ) => {
								const selectedAuthors = values.postAuthor || [];
								return (
									<CheckboxControl
										__nextHasNoMarginBottom
										key={ userIndex }
										label={ user.label }
										checked={ selectedAuthors.includes( user.value ) }
										onChange={ ( isChecked ) => {
											const newAuthors = isChecked
												? [ ...selectedAuthors, user.value ]
												: selectedAuthors.filter( ( a ) => a !== user.value );
											updateConditionValue( groupIndex, conditionIndex, 'postAuthor', newAuthors );
										} }
									/>
								);
							} ) }
						</BaseControl>
					);

				case 'customField':
					return (
						<>
						<TextControl
								label={ __( 'Custom Field Name', 'vk-dynamic-if-block' ) }
								value={ values.customFieldName || '' }
							onChange={ ( value ) =>
									updateConditionValue( groupIndex, conditionIndex, 'customFieldName', value )
							}
						/>
							{ values.customFieldName && (
							<>
								<SelectControl
										label={ __( 'Custom Field Rule', 'vk-dynamic-if-block' ) }
										value={ values.customFieldRule || '' }
									options={ [
										{
											value: 'valueExists',
												label: __( 'Value Exist ( !empty() )', 'vk-dynamic-if-block' ),
										},
										{
											value: 'valueEquals',
												label: __( 'Value Equals ( === )', 'vk-dynamic-if-block' ),
										},
									] }
									onChange={ ( value ) =>
											updateConditionValue( groupIndex, conditionIndex, 'customFieldRule', value )
									}
								/>
									{ values.customFieldRule === 'valueEquals' && (
										<TextControl
											label={ __( 'Custom Field Value', 'vk-dynamic-if-block' ) }
											value={ values.customFieldValue || '' }
											onChange={ ( value ) =>
												updateConditionValue( groupIndex, conditionIndex, 'customFieldValue', value )
											}
										/>
								) }
							</>
							) }
						</>
					);

				case 'period':
					return (
						<>
							<SelectControl
								label={ __( 'Display Period Setting', 'vk-dynamic-if-block' ) }
								value={ values.periodDisplaySetting || 'none' }
								options={ [
									{
										value: 'none',
										label: __( 'No restriction', 'vk-dynamic-if-block' ),
									},
									{
										value: 'deadline',
										label: __( 'Set to display deadline', 'vk-dynamic-if-block' ),
									},
									{
										value: 'startline',
										label: __( 'Set to display startline', 'vk-dynamic-if-block' ),
									},
									{
										value: 'daysSincePublic',
										label: __( 'Number of days from the date of publication', 'vk-dynamic-if-block' ),
									},
								] }
								onChange={ ( value ) =>
									updateConditionValue( groupIndex, conditionIndex, 'periodDisplaySetting', value )
								}
							/>
							{ values.periodDisplaySetting && values.periodDisplaySetting !== 'none' && (
								<>
									<SelectControl
										label={ __( 'Period specification method', 'vk-dynamic-if-block' ) }
										value={ values.periodSpecificationMethod || 'direct' }
										options={ [
											{
												value: 'direct',
												label: __( 'Direct input in this block', 'vk-dynamic-if-block' ),
											},
											{
												value: 'referCustomField',
												label: __( 'Refer to value of custom field', 'vk-dynamic-if-block' ),
											},
										] }
										onChange={ ( value ) =>
											updateConditionValue( groupIndex, conditionIndex, 'periodSpecificationMethod', value )
										}
									/>
									{ values.periodSpecificationMethod === 'direct' && (
										<NumberControl
											label={ __( 'Value for the specified period', 'vk-dynamic-if-block' ) }
											type={
												values.periodDisplaySetting === 'daysSincePublic'
													? 'number'
													: 'datetime-local'
											}
											step={
												values.periodDisplaySetting === 'daysSincePublic'
													? 1
													: 60
											}
											value={ values.periodDisplayValue || '' }
											onChange={ ( value ) =>
												updateConditionValue( groupIndex, conditionIndex, 'periodDisplayValue', value )
											}
										/>
									) }
									{ values.periodSpecificationMethod === 'referCustomField' && (
										<>
											<TextControl
												label={ __( 'Referenced custom field name', 'vk-dynamic-if-block' ) }
												value={ values.periodReferCustomField || '' }
												onChange={ ( value ) =>
													updateConditionValue( groupIndex, conditionIndex, 'periodReferCustomField', value )
												}
											/>
											{ ! values.periodReferCustomField && (
												<div className="vkdif__alert vkdif__alert-warning">
													{ __(
														'Enter the name of the custom field you wish to reference.',
														'vk-dynamic-if-block'
													) }
												</div>
											) }
										</>
									) }
								</>
							) }
						</>
					);

				case 'loginUser':
					return (
						<ToggleControl
							label={ __( 'Displayed only for logged-in users.', 'vk-dynamic-if-block' ) }
							checked={ values.showOnlyLoginUser || false }
							onChange={ ( checked ) =>
								updateConditionValue( groupIndex, conditionIndex, 'showOnlyLoginUser', checked )
							}
						/>
					);

				default:
					return null;
			}
		};

		// 複数選択値を適切にフォーマットする関数
		const formatMultipleValues = ( selectedValues, options, typeName ) => {
			if ( selectedValues.length === 0 ) {
				return { display: `All ${ typeName }`, tooltip: null };
			}

			if ( selectedValues.length === 1 ) {
				const option = options.find( ( opt ) => opt.value === selectedValues[0] );
				return { 
					display: option ? option.label : selectedValues[0], 
					tooltip: null 
				};
			}

			// 複数選択の場合は最初の2個まで表示
			const maxDisplay = 2;
			const displayValues = selectedValues.slice( 0, maxDisplay );
			const remainingCount = selectedValues.length - maxDisplay;

			const displayLabels = displayValues.map( ( value ) => {
				const option = options.find( ( opt ) => opt.value === value );
				return option ? option.label : value;
			} );

			let displayText;
			if ( remainingCount > 0 ) {
				displayText = `${ displayLabels.join( ', ' ) } +${ remainingCount }`;
			} else {
				displayText = displayLabels.join( ', ' );
			}

			// ツールチップ用の全選択項目
			const allLabels = selectedValues.map( ( value ) => {
				const option = options.find( ( opt ) => opt.value === value );
				return option ? option.label : value;
			} );

			return {
				display: displayText,
				tooltip: allLabels.join( '\n' )
			};
		};

		const generateLabels = () => {
			// グループごとにラベルを生成
			const groupLabels = conditions.map((group) => {
				const { conditions: groupConditions, operator } = group;
				if (groupConditions.length === 0) return null;
				const condition = groupConditions[0];
				let label = '';
				switch (condition.type) {
					case 'pageType': {
						const selected = condition.values.ifPageType || [];
						label = selected.map(val => {
							const opt = ifPageTypes.find(o => o.value === val);
							return opt ? opt.label : val;
						}).join(' or ');
						break;
					}
					case 'postType': {
						const selected = condition.values.ifPostType || [];
						label = selected.map(val => {
							const opt = vk_dynamic_if_block_localize_data.postTypeSelectOptions.find(o => o.value === val);
							return opt ? opt.label : val;
						}).join(' or ');
						break;
					}
					case 'language': {
						const selected = condition.values.ifLanguage || [];
						label = selected.map(val => {
							const opt = vk_dynamic_if_block_localize_data.languageSelectOptions.find(o => o.value === val);
							return opt ? opt.label : val;
						}).join(' or ');
						break;
					}
					case 'userRole': {
						const selected = condition.values.userRole || [];
						label = selected.map(val => {
							const opt = userRoles.find(o => o.value === val);
							return opt ? opt.label : val;
						}).join(operator === 'and' ? ' and ' : ' or ');
						break;
					}
					case 'postAuthor': {
						const selected = condition.values.postAuthor || [];
						label = selected.map(val => {
							const opt = userSelectOptions.find(o => o.value === val);
							return opt ? opt.label : val;
						}).join(operator === 'and' ? ' and ' : ' or ');
						break;
					}
					case 'customField':
						label = condition.values.customFieldName || 'Custom Field';
						break;
					case 'period':
						label = condition.values.periodDisplaySetting || 'Period';
						break;
					case 'loginUser':
						label = __('Login User Only', 'vk-dynamic-if-block');
						break;
					default:
						label = condition.type;
				}
				return `[${label}]`;
			}).filter(Boolean);

			if (groupLabels.length === 0) {
				return {
					display: __('No conditions set', 'vk-dynamic-if-block'),
					tooltip: null
				};
			}

			let labelsString = groupLabels.join(` OR `);
			if (exclusion && groupLabels.length > 0) {
				labelsString = __('!', 'vk-dynamic-if-block') + ' ' + labelsString;
			}

			return {
				display: labelsString,
				tooltip: null
			};
		};

		const blockClassName = 'vk-dynamic-if-block';
		const MY_TEMPLATE = [ [ 'core/paragraph', {} ] ];

		return (
			<div { ...useBlockProps( { className: blockClassName } ) }>
				<InspectorControls>
					<PanelBody
						title={ __( 'Display Conditions', 'vk-dynamic-if-block' ) }
						className={ 'vkdif' }
					>
						{ conditions.length === 0 ? (
							<div>
								<p>{ __( 'No conditions set. Add a condition to control display.', 'vk-dynamic-if-block' ) }</p>
								<Button
									variant="primary"
									onClick={ addCondition }
								>
									{ __( 'Add Condition', 'vk-dynamic-if-block' ) }
								</Button>
							</div>
						) : (
							<>
								{ conditions.map( ( group, groupIndex ) => (
									<div key={ group.id } className="vkdif__group">
										<div className="vkdif__group-header">
											<span className="vkdif__group-number">Condition {groupIndex + 1}</span>
										</div>
										<div className="vkdif__group-conditions">
											{ group.conditions.map( ( condition, conditionIndex ) => {
												// 他グループで選択済みのCondition Typeを取得
												const usedTypes = conditions
													.filter((_, idx) => idx !== groupIndex)
													.map(g => g.conditions[0]?.type)
													.filter(Boolean);
												// 選択肢をdisabled付きで生成
												const availableConditionTypes = conditionTypes.map(opt => ({
													...opt,
													disabled: usedTypes.includes(opt.value) && opt.value !== condition.type
												}));
												return (
													<div key={ condition.id } className="vkdif__condition">
														<div className="vkdif__condition-header">
															<SelectControl
																label={ __( 'Condition Type', 'vk-dynamic-if-block' ) }
																value={ condition.type }
																options={ availableConditionTypes }
																onChange={ ( value ) =>
																	updateCondition( groupIndex, conditionIndex, { type: value, values: {} } )
																}
															/>
														</div>
														<div className="vkdif__condition-settings">
															{ renderConditionSettings( condition, groupIndex, conditionIndex ) }
														</div>
													</div>
												);
											} ) }
										</div>
										<Button
											variant="secondary"
											isDestructive
											onClick={ () => {
												const newConditions = [ ...conditions ];
												newConditions.splice( groupIndex, 1 );
												setAttributes( { conditions: newConditions } );
											} }
										>
											{ __( 'Remove Condition', 'vk-dynamic-if-block' ) }
										</Button>
									</div>
								) ) }
								<Button
									variant="secondary"
									onClick={ addGroup }
									className="vkdif__add-condition"
								>
									{ __( 'Add Condition', 'vk-dynamic-if-block' ) }
								</Button>
								<ToggleControl
									label={ __( 'Exclusion designation', 'vk-dynamic-if-block' ) }
									checked={ exclusion }
									onChange={ ( checked ) =>
										setAttributes( { exclusion: checked } )
									}
								/>
							</>
						) }
					</PanelBody>
				</InspectorControls>
				<div className="vk-dynamic-if-block__label">
					<span 
						className={ generateLabels().tooltip ? 'vk-dynamic-if-block__label--has-tooltip' : '' }
						onMouseEnter={ () => {
							if ( generateLabels().tooltip ) {
								setTooltipContent( generateLabels().tooltip );
								setShowTooltip( true );
							}
						} }
						onMouseLeave={ () => {
							setShowTooltip( false );
						} }
					>
						{ generateLabels().display || __( 'No conditions set', 'vk-dynamic-if-block' ) }
					</span>
					{ showTooltip && tooltipContent && (
						<div className="vk-dynamic-if-block__tooltip">
							{ tooltipContent.split( '\n' ).map( ( line, index ) => (
								<div key={ index }>{ line }</div>
							) ) }
						</div>
					) }
				</div>

				<InnerBlocks template={ MY_TEMPLATE } />
			</div>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},
} );
