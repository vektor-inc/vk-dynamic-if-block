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
} from '@wordpress/components';
import { ReactComponent as Icon } from './icon.svg';
import transforms from './transforms';
import React from 'react';

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
                                       name: 'Condition 1',
                                       conditions: [],
                                       operator: 'and',
                               },
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
               ifPageType: {
                       type: 'string',
                       default: 'none',
               },
               ifPostType: {
                       type: 'string',
                       default: 'none',
               },
               ifLanguage: {
                       type: 'string',
                       default: 'none',
               },
               userRole: {
                       type: 'array',
                       default: [],
               },
               postAuthor: {
                       type: 'number',
                       default: 0,
               },
               customFieldName: {
                       type: 'string',
                       default: '',
               },
               customFieldRule: {
                       type: 'string',
                       default: 'valueExists',
               },
               customFieldValue: {
                       type: 'string',
                       default: '',
               },
               periodDisplaySetting: {
                       type: 'string',
                       default: 'none',
               },
               periodSpecificationMethod: {
                       type: 'string',
                       default: 'direct',
               },
               periodDisplayValue: {
                       type: 'string',
                       default: '',
               },
               periodReferCustomField: {
                       type: 'string',
                       default: '',
               },
               showOnlyLoginUser: {
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

               // 移行処理のヘルパー関数
               const createConditionGroup = (type, values, groupIndex) => ({
                       id: Date.now() + groupIndex,
                       name: `Condition ${groupIndex}`,
                       conditions: [{ id: Date.now() + groupIndex - 1, type, values }],
                       operator: 'and',
               });

               // 既存ブロックから新形式への移行処理
               React.useEffect( () => {
                       if ( !conditions || conditions.length === 0 || ( conditions[0] && conditions[0].conditions.length === 0 ) ) {
                               const newConditions = [];
                               let groupIndex = 1;

                               // 移行対象の条件を定義
                               const migrationRules = [
                                       { attr: 'ifPageType', type: 'pageType', key: 'ifPageType', condition: val => val && val !== 'none' },
                                       { attr: 'ifPostType', type: 'postType', key: 'ifPostType', condition: val => val && val !== 'none' },
                                       { attr: 'ifLanguage', type: 'language', key: 'ifLanguage', condition: val => val && val !== 'none' },
                                       { attr: 'userRole', type: 'userRole', key: 'userRole', condition: val => val && val.length > 0 },
                                       { attr: 'postAuthor', type: 'postAuthor', key: 'postAuthor', condition: val => val && val > 0 },
                                       { attr: 'customFieldName', type: 'customField', key: null, condition: val => val, customValues: () => ({
                                               customFieldName: attributes.customFieldName,
                                               ...( attributes.customFieldRule ? { customFieldRule: attributes.customFieldRule } : {} ),
                                               ...( attributes.customFieldValue ? { customFieldValue: attributes.customFieldValue } : {} ),
                                       })},
                                       { attr: 'periodDisplaySetting', type: 'period', key: null, condition: val => val && val !== 'none', customValues: () => ({
                                               periodDisplaySetting: attributes.periodDisplaySetting,
                                               ...( attributes.periodSpecificationMethod ? { periodSpecificationMethod: attributes.periodSpecificationMethod } : {} ),
                                               ...( attributes.periodDisplayValue ? { periodDisplayValue: attributes.periodDisplayValue } : {} ),
                                               ...( attributes.periodReferCustomField ? { periodReferCustomField: attributes.periodReferCustomField } : {} ),
                                       })},
                                       { attr: 'showOnlyLoginUser', type: 'loginUser', key: 'showOnlyLoginUser', condition: val => val },
                               ];

                               // 各条件を移行
                               migrationRules.forEach(rule => {
                                       const value = attributes[rule.attr];
                                       if (rule.condition(value)) {
                                               const values = rule.customValues ? rule.customValues() : { [rule.key]: Array.isArray(value) ? value : [value] };
                                               newConditions.push(createConditionGroup(rule.type, values, groupIndex++));
                                       }
                               });

                               // 条件が1つもない場合は、デフォルトのCondition 1を作成
                               if (newConditions.length === 0) {
                                       newConditions.push(createConditionGroup('pageType', {}, 1));
                               }

                               setAttributes( { conditions: newConditions } );
                       }
               }, [] );

		const conditionTypes = [
			'pageType', 'postType', 'language', 'userRole', 'postAuthor', 'customField', 'period', 'loginUser'
		].map(value => ({
			value,
			label: __(value === 'pageType' ? 'Page Type' : 
					 value === 'postType' ? 'Post Type' : 
					 value === 'language' ? 'Language' : 
					 value === 'userRole' ? 'User Role' : 
					 value === 'postAuthor' ? 'Post Author' : 
					 value === 'customField' ? 'Custom Field' : 
					 value === 'period' ? 'Display Period' : 
					 'Login User Only', 'vk-dynamic-if-block'),
		}));

		const pageTypeDefinitions = [
			{ value: 'none', label: 'No restriction' },
			{ value: 'is_front_page', label: 'Front Page', func: 'is_front_page()' },
			{ value: 'is_single', label: 'Single', func: 'is_single()' },
			{ value: 'is_page', label: 'Page', func: 'is_page()' },
			{ value: 'is_singular', label: 'Singular', func: 'is_singular()' },
			{ value: 'is_home', label: 'Post Top', func: 'is_home() && ! is_front_page()' },
			{ value: 'is_post_type_archive', label: 'Post Type Archive', func: 'is_post_type_archive()' },
			{ value: 'is_category', label: 'Category Archive', func: 'is_category()' },
			{ value: 'is_tag', label: 'Tag Archive', func: 'is_tag()' },
			{ value: 'is_tax', label: 'Taxonomy Archive', func: 'is_tax()' },
			{ value: 'is_year', label: 'Yearly Archive', func: 'is_year()' },
			{ value: 'is_month', label: 'Monthly Archive', func: 'is_month()' },
			{ value: 'is_date', label: 'Daily Archive', func: 'is_date()' },
			{ value: 'is_author', label: 'Author Archive', func: 'is_author()' },
			{ value: 'is_archive', label: 'Archive', func: 'is_archive()' },
			{ value: 'is_search', label: 'Search Result', func: 'is_search()' },
			{ value: 'is_404', label: '404', func: 'is_404()' },
		];

		const ifPageTypes = pageTypeDefinitions.map(def => ({
			value: def.value,
			label: def.func ? __(def.label, 'vk-dynamic-if-block') + ` ( ${def.func} )` : __(def.label, 'vk-dynamic-if-block'),
			simpleLabel: __(def.label, 'vk-dynamic-if-block'),
		}));

		const userRoles = Object.entries(vk_dynamic_if_block_localize_data.userRoles || {}).map(([key, label]) => ({
			value: key,
			label: __(label, 'vk-dynamic-if-block'),
		}));

		const userSelectOptions = vk_dynamic_if_block_localize_data.userSelectOptions || [];

		const addCondition = () => {
			const newCondition = { id: Date.now(), type: 'pageType', values: {} };
			const newConditions = [ ...conditions ];
			
			if ( newConditions.length === 0 ) {
				newConditions.push( {
					id: 'default-group',
					name: 'Condition 1',
					conditions: [ newCondition ],
					operator: 'and'
				} );
			} else {
				newConditions[0].conditions.push( newCondition );
			}

			setAttributes( { conditions: newConditions } );
		};

		const addConditionGroup = () => {
			const usedTypes = conditions.map(g => g.conditions[0]?.type).filter(Boolean);
			const availableTypes = conditionTypes.map(opt => opt.value).filter(val => !usedTypes.includes(val));
			const firstType = availableTypes[0] || 'pageType';
			const newConditionGroup = {
				id: Date.now(),
				name: `Condition ${ conditions.length + 1 }`,
				conditions: [{ id: Date.now(), type: firstType, values: {} }],
				operator: 'or',
			};
			setAttributes( { conditions: [ ...conditions, newConditionGroup ] } );
		};

		const updateCondition = ( groupIndex, conditionIndex, updates ) => {
			if (!Array.isArray(conditions) || groupIndex < 0 || conditionIndex < 0 || !updates) return;
			
			const newConditions = [ ...conditions ];
			const group = newConditions[ groupIndex ];
			const condition = group?.conditions?.[ conditionIndex ];
			
			if (!group || !condition) return;
			
			newConditions[ groupIndex ].conditions[ conditionIndex ] = { ...condition, ...updates };
			setAttributes( { conditions: newConditions } );
		};

		const updateConditionValue = ( groupIndex, conditionIndex, key, value ) => {
			if (!Array.isArray(conditions) || groupIndex < 0 || conditionIndex < 0) return;
			
			const newConditions = [ ...conditions ];
			const group = newConditions[ groupIndex ];
			const condition = group?.conditions?.[ conditionIndex ];
			
			if (!group || !condition) return;
			
			condition.values = { ...condition.values, [ key ]: value };
			setAttributes( { conditions: newConditions } );
		};

		// 共通のチェックボックスレンダラー
		const renderCheckboxGroup = (options = [], selectedValues = [], valueKey = '', className = '', groupIndex = 0, conditionIndex = 0) => {
			if (!Array.isArray(options) || !options.length) return null;
			
			return (
				<BaseControl __nextHasNoMarginBottom className={className}>
					{options.map((option, index) => {
						const selected = Array.isArray(selectedValues) ? selectedValues : [];
						const isChecked = selected.includes(option?.value);
						
						return (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={option?.value || index}
								label={option?.label || ''}
								checked={isChecked}
								onChange={(isChecked) => {
									const newValues = isChecked
										? [...selected, option.value]
										: selected.filter(v => v !== option.value);
									updateConditionValue(groupIndex, conditionIndex, valueKey, newValues);
								}}
							/>
						);
					})}
				</BaseControl>
			);
		};

		const renderConditionSettings = ( condition, groupIndex, conditionIndex ) => {
			const { type, values } = condition;

			switch ( type ) {
				case 'pageType':
					return renderCheckboxGroup(
						ifPageTypes,
						values.ifPageType,
						'ifPageType',
						'dynamic-if-page-type',
						groupIndex,
						conditionIndex
					);

				case 'postType':
					return renderCheckboxGroup(
						vk_dynamic_if_block_localize_data.postTypeSelectOptions,
						values.ifPostType,
						'ifPostType',
						'dynamic-if-post-type',
						groupIndex,
						conditionIndex
					);

				case 'language':
					return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-language"
						>
							{ (() => {
								const allLanguages = vk_dynamic_if_block_localize_data.languageSelectOptions || [];
								const currentSiteLanguage = vk_dynamic_if_block_localize_data.currentSiteLanguage || '';
								const selectedLanguages = values.ifLanguage || [];
								
								// 言語オプションを並び替えて、Unspecified、現在のサイト言語、その他の言語の順に表示
								const sortedLanguages = allLanguages.sort((a, b) => {
									// Unspecifiedを最上部に
									if (a.value === '') return -1;
									if (b.value === '') return 1;
									// 現在のサイト言語を2番目に
									if (a.value === currentSiteLanguage) return -1;
									if (b.value === currentSiteLanguage) return 1;
									// 英語を3番目に
									if (a.value === 'en_US') return -1;
									if (b.value === 'en_US') return 1;
									// その他はアルファベット順
									return a.label.localeCompare(b.label);
								});
								
								return sortedLanguages.map((language, languageIndex) => {
									const isCurrentSiteLanguage = language.value === currentSiteLanguage;
									const label = isCurrentSiteLanguage ? `${language.label}` : language.label;
									
									return (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={ languageIndex }
											label={ label }
											checked={ selectedLanguages.includes(language.value) }
											onChange={ ( isChecked ) => {
												const newLanguages = isChecked
													? [ ...selectedLanguages, language.value ]
													: selectedLanguages.filter( ( l ) => l !== language.value );
												updateConditionValue( groupIndex, conditionIndex, 'ifLanguage', newLanguages );
											} }
										/>
									);
								});
							})() }
						</BaseControl>
					);

				case 'userRole':
					return renderCheckboxGroup(
						userRoles,
						values.userRole,
						'userRole',
						'dynamic-if-user-role',
						groupIndex,
						conditionIndex
					);

				case 'postAuthor':
					return renderCheckboxGroup(
						userSelectOptions,
						values.postAuthor,
						'postAuthor',
						'dynamic-if-post-author',
						groupIndex,
						conditionIndex
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

		// 共通のラベル生成関数
		const generateLabelFromValues = (values = {}, options = [], valueKey = '', useSimpleLabel = false) => {
			const selected = Array.isArray(values[valueKey]) ? values[valueKey] : [];
			if (!selected.length || !Array.isArray(options)) return null;
			
			return selected.map(val => 
				options.find(o => o?.value === val)?.[useSimpleLabel ? 'simpleLabel' : 'label'] || val
			).join(', ');
		};

		const generateLabels = () => {
			if (!Array.isArray(conditions) || !conditions.length) {
				return exclusion ? 
					`${__('!', 'vk-dynamic-if-block')} ${__('No conditions set', 'vk-dynamic-if-block')}` :
					__('No conditions set', 'vk-dynamic-if-block');
			}

			const groupLabels = conditions.map((group) => {
				const { conditions: groupConditions = [] } = group || {};
				if (!groupConditions.length) return null;
				
				const condition = groupConditions[0];
				if (!condition?.type) return null;
				
				const { values = {} } = condition;
				const labelMap = {
					pageType: () => generateLabelFromValues(values, ifPageTypes, 'ifPageType', true),
					postType: () => generateLabelFromValues(values, vk_dynamic_if_block_localize_data?.postTypeSelectOptions || [], 'ifPostType'),
					language: () => generateLabelFromValues(values, vk_dynamic_if_block_localize_data?.languageSelectOptions || [], 'ifLanguage'),
					userRole: () => generateLabelFromValues(values, userRoles, 'userRole'),
					postAuthor: () => generateLabelFromValues(values, userSelectOptions, 'postAuthor'),
					customField: () => values.customFieldName || null,
					period: () => values.periodDisplaySetting && values.periodDisplaySetting !== 'none' ? values.periodDisplaySetting : null,
					loginUser: () => values.showOnlyLoginUser ? __('Login User Only', 'vk-dynamic-if-block') : null,
				};

				const label = labelMap[condition.type]?.() || condition.type;
				return label ? `[${label}]` : null;
			}).filter(Boolean);

			if (!groupLabels.length) {
				return exclusion ? 
					`${__('!', 'vk-dynamic-if-block')} ${__('No conditions set', 'vk-dynamic-if-block')}` :
					__('No conditions set', 'vk-dynamic-if-block');
			}

			const labelsString = groupLabels.join(` ${conditionOperator?.toUpperCase() || 'AND'} `);
			return exclusion ? `${__('!', 'vk-dynamic-if-block')} ${labelsString}` : labelsString;
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
								<BaseControl
									__nextHasNoMarginBottom
									className="dynamic-if-add-condition"
								>
									<p>{ __( 'No conditions set. Add a condition to control display.', 'vk-dynamic-if-block' ) }</p>
									<Button
										variant="primary"
										onClick={ addCondition }
									>
										{ __( 'Add Condition', 'vk-dynamic-if-block' ) }
									</Button>
								</BaseControl>
								<ToggleControl
									label={ __( 'Exclusion designation', 'vk-dynamic-if-block' ) }
									checked={ exclusion }
									onChange={ ( checked ) =>
										setAttributes( { exclusion: checked } )
									}
								/>
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
								<BaseControl
									__nextHasNoMarginBottom
									className="dynamic-if-add-condition"
								>
									<Button
										variant="secondary"
										onClick={ addConditionGroup }
										className="vkdif__add-condition"
									>
										{ __( 'Add Condition', 'vk-dynamic-if-block' ) }
									</Button>
								</BaseControl>
								{ conditions.length > 1 && (
									<SelectControl
										label={ __( 'Condition Operator', 'vk-dynamic-if-block' ) }
										value={ conditionOperator }
										options={ [
											{ label: 'AND', value: 'and' },
											{ label: 'OR', value: 'or' }
										] }
										onChange={ ( value ) => setAttributes( { conditionOperator: value } ) }
									/>
								) }
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
					<span>
						{ generateLabels() || __( 'No conditions set', 'vk-dynamic-if-block' ) }
					</span>
				</div>

				<InnerBlocks template={ MY_TEMPLATE } />
			</div>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},
} );
