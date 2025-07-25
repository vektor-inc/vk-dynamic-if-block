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
} from '@wordpress/components';
import { ReactComponent as Icon } from './icon.svg';
import transforms from './transforms';
import React from 'react';
import {
	CONDITION_TYPE_LABELS,
	PAGE_TYPE_DEFINITIONS,
	CUSTOM_FIELD_RULES,
	PERIOD_SETTINGS,
	PERIOD_METHODS,
	CONDITION_OPERATORS,
	BLOCK_CONFIG,
	createMigrationRules,
	generateId,
	createConditionGroup,
	sortLanguages,
} from './constants';

// グローバル変数の宣言
/* global vkDynamicIfBlockLocalizeData */

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
	edit: function Edit( { attributes, setAttributes } ) {
		const { conditions, conditionOperator, exclusion } = attributes;

		// 既存ブロックから新形式への移行処理
		React.useEffect( () => {
			if (
				! conditions ||
				conditions.length === 0 ||
				( conditions[ 0 ] && conditions[ 0 ].conditions.length === 0 )
			) {
				const newConditions = [];
				let groupIndex = 1;

				// 移行対象の条件を定義
				const migrationRules = createMigrationRules( attributes );

				// 各条件を移行
				migrationRules.forEach( ( rule ) => {
					const value = attributes[ rule.attr ];
					if ( rule.condition( value ) ) {
						const values = rule.customValues
							? rule.customValues()
							: {
									[ rule.key ]: Array.isArray( value )
										? value
										: [ value ],
							  };
						newConditions.push(
							createConditionGroup(
								rule.type,
								values,
								groupIndex++
							)
						);
					}
				} );

				// 条件が1つもない場合は、デフォルトのCondition 1を作成
				if ( newConditions.length === 0 ) {
					// デフォルトでは何も制限しない（常に表示）
					newConditions.push(
						createConditionGroup(
							'pageType',
							{ ifPageType: [ 'none' ] },
							1
						)
					);
				}

				setAttributes( { conditions: newConditions } );
			}
		}, [ attributes, conditions, setAttributes ] );

		const conditionTypes = Object.entries( CONDITION_TYPE_LABELS ).map(
			( [ value, label ] ) => ( {
				value,
				// eslint-disable-next-line @wordpress/i18n-no-variables
				label: __( label, 'vk-dynamic-if-block' ),
			} )
		);

		const ifPageTypes = PAGE_TYPE_DEFINITIONS.map( ( def ) => {
			// eslint-disable-next-line @wordpress/i18n-no-variables
			const translatedLabel = __( def.label, 'vk-dynamic-if-block' );

			return {
				value: def.value,
				label: def.func
					? `${ translatedLabel } ( ${ def.func } )`
					: translatedLabel,
				simpleLabel: translatedLabel,
			};
		} );

		const userRoles = Object.entries(
			vkDynamicIfBlockLocalizeData?.userRoles || {}
		).map( ( [ key, label ] ) => ( {
			value: key,
			// eslint-disable-next-line @wordpress/i18n-no-variables
			label: __( label, 'vk-dynamic-if-block' ),
		} ) );

		const userSelectOptions =
			vkDynamicIfBlockLocalizeData?.userSelectOptions || [];

		const addCondition = () => {
			const newCondition = {
				id: generateId(),
				type: BLOCK_CONFIG.defaultConditionType,
				values: {},
			};
			const newConditions = [ ...conditions ];

			if ( newConditions.length === 0 ) {
				newConditions.push( {
					id: 'default-group',
					name: 'Condition 1',
					conditions: [ newCondition ],
					operator: BLOCK_CONFIG.defaultOperator,
				} );
			} else {
				newConditions[ 0 ].conditions.push( newCondition );
			}

			setAttributes( { conditions: newConditions } );
		};

		const addConditionGroup = () => {
			// Condition Typeの種類数に制限
			const maxConditions = Object.keys( CONDITION_TYPE_LABELS ).length;
			if ( conditions.length >= maxConditions ) {
				return; // 最大数に達した場合は何もしない
			}

			const usedTypes = conditions
				.map( ( g ) => g.conditions[ 0 ]?.type )
				.filter( Boolean );

			const availableTypes = conditionTypes
				.map( ( opt ) => opt.value )
				.filter( ( val ) => ! usedTypes.includes( val ) );
			const firstType =
				availableTypes[ 0 ] || BLOCK_CONFIG.defaultConditionType;

			// デフォルト値を設定
			let defaultValues = {};
			if ( firstType === 'pageType' ) {
				defaultValues = { ifPageType: [ 'none' ] };
			} else if ( firstType === 'postType' ) {
				defaultValues = { ifPostType: [ 'none' ] };
			} else if ( firstType === 'language' ) {
				defaultValues = { ifLanguage: [ '' ] };
			} else if ( firstType === 'postAuthor' ) {
				defaultValues = { postAuthor: [ 0 ] };
			}

			const newConditionGroup = {
				id: generateId(),
				name: `Condition ${ conditions.length + 1 }`,
				conditions: [
					{
						id: generateId(),
						type: firstType,
						values: defaultValues,
					},
				],
				operator: 'or',
			};
			setAttributes( {
				conditions: [ ...conditions, newConditionGroup ],
			} );
		};

		const updateCondition = ( groupIndex, conditionIndex, updates ) => {
			if (
				! Array.isArray( conditions ) ||
				groupIndex < 0 ||
				conditionIndex < 0 ||
				! updates
			) {
				return;
			}

			const newConditions = [ ...conditions ];
			const group = newConditions[ groupIndex ];
			const condition = group?.conditions?.[ conditionIndex ];

			if ( ! group || ! condition ) {
				return;
			}

			newConditions[ groupIndex ].conditions[ conditionIndex ] = {
				...condition,
				...updates,
			};
			setAttributes( { conditions: newConditions } );
		};

		const updateConditionValue = (
			groupIndex,
			conditionIndex,
			key,
			value
		) => {
			if (
				! Array.isArray( conditions ) ||
				groupIndex < 0 ||
				conditionIndex < 0
			) {
				return;
			}

			const newConditions = [ ...conditions ];
			const group = newConditions[ groupIndex ];
			const condition = group?.conditions?.[ conditionIndex ];

			if ( ! group || ! condition ) {
				return;
			}

			condition.values = { ...condition.values, [ key ]: value };
			setAttributes( { conditions: newConditions } );
		};

		// 共通のチェックボックスレンダラー
		const renderCheckboxGroup = (
			options = [],
			selectedValues = [],
			valueKey = '',
			className = '',
			groupIndex = 0,
			conditionIndex = 0
		) => {
			if ( ! Array.isArray( options ) || ! options.length ) {
				return null;
			}

			return (
				<BaseControl __nextHasNoMarginBottom className={ className }>
					{ options.map( ( option, index ) => {
						const selected = Array.isArray( selectedValues )
							? selectedValues
							: [];
						const isChecked = selected.includes( option?.value );

						return (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ option?.value || index }
								label={ option?.label || '' }
								checked={ isChecked }
								onChange={ ( checked ) => {
									const newValues = checked
										? [ ...selected, option.value ]
										: selected.filter(
												( v ) => v !== option.value
										  );
									updateConditionValue(
										groupIndex,
										conditionIndex,
										valueKey,
										newValues
									);
								} }
							/>
						);
					} ) }
				</BaseControl>
			);
		};

		const renderConditionSettings = (
			condition = {},
			groupIndex = 0,
			conditionIndex = 0
		) => {
			const { type = '', values = {} } = condition;
			const updateValue = ( key, value ) =>
				updateConditionValue( groupIndex, conditionIndex, key, value );

			const renderers = {
				pageType: () =>
					renderCheckboxGroup(
						ifPageTypes,
						values.ifPageType,
						'ifPageType',
						'dynamic-if-page-type',
						groupIndex,
						conditionIndex
					),
				postType: () =>
					renderCheckboxGroup(
						vkDynamicIfBlockLocalizeData?.postTypeSelectOptions ||
							[],
						values.ifPostType,
						'ifPostType',
						'dynamic-if-post-type',
						groupIndex,
						conditionIndex
					),
				language: () => {
					const allLanguages =
						vkDynamicIfBlockLocalizeData?.languageSelectOptions ||
						[];
					const currentSiteLanguage =
						vkDynamicIfBlockLocalizeData?.currentSiteLanguage || '';
					const selectedLanguages = values.ifLanguage || [];
					const sortedLanguages = sortLanguages(
						allLanguages,
						currentSiteLanguage
					);

					return (
						<BaseControl
							__nextHasNoMarginBottom
							className="dynamic-if-language"
						>
							{ sortedLanguages.map( ( language, index ) => (
								<CheckboxControl
									__nextHasNoMarginBottom
									key={ language.value || index }
									label={ language.label }
									checked={ selectedLanguages.includes(
										language.value
									) }
									onChange={ ( checked ) => {
										const newLanguages = checked
											? [
													...selectedLanguages,
													language.value,
											  ]
											: selectedLanguages.filter(
													( l ) =>
														l !== language.value
											  );
										updateValue(
											'ifLanguage',
											newLanguages
										);
									} }
								/>
							) ) }
						</BaseControl>
					);
				},
				userRole: () =>
					renderCheckboxGroup(
						userRoles,
						values.userRole,
						'userRole',
						'dynamic-if-user-role',
						groupIndex,
						conditionIndex
					),
				postAuthor: () =>
					renderCheckboxGroup(
						userSelectOptions,
						values.postAuthor,
						'postAuthor',
						'dynamic-if-post-author',
						groupIndex,
						conditionIndex
					),
				customField: () => (
					<>
						<TextControl
							label={ __(
								'Custom Field Name',
								'vk-dynamic-if-block'
							) }
							value={ values.customFieldName || '' }
							onChange={ ( value ) =>
								updateValue( 'customFieldName', value )
							}
						/>
						{ values.customFieldName && (
							<>
								<SelectControl
									label={ __(
										'Custom Field Rule',
										'vk-dynamic-if-block'
									) }
									value={ values.customFieldRule || '' }
									options={ CUSTOM_FIELD_RULES }
									onChange={ ( value ) =>
										updateValue( 'customFieldRule', value )
									}
								/>
								{ values.customFieldRule === 'valueEquals' && (
									<TextControl
										label={ __(
											'Custom Field Value',
											'vk-dynamic-if-block'
										) }
										value={ values.customFieldValue || '' }
										onChange={ ( value ) =>
											updateValue(
												'customFieldValue',
												value
											)
										}
									/>
								) }
							</>
						) }
					</>
				),
				period: () => (
					<>
						<SelectControl
							label={ __(
								'Display Period Setting',
								'vk-dynamic-if-block'
							) }
							value={ values.periodDisplaySetting || 'none' }
							options={ PERIOD_SETTINGS }
							onChange={ ( value ) =>
								updateValue( 'periodDisplaySetting', value )
							}
						/>
						{ values.periodDisplaySetting &&
							values.periodDisplaySetting !== 'none' && (
								<>
									<SelectControl
										label={ __(
											'Period specification method',
											'vk-dynamic-if-block'
										) }
										value={
											values.periodSpecificationMethod ||
											'direct'
										}
										options={ PERIOD_METHODS }
										onChange={ ( value ) =>
											updateValue(
												'periodSpecificationMethod',
												value
											)
										}
									/>
									{ values.periodSpecificationMethod ===
										'direct' && (
										<TextControl
											label={ __(
												'Value for the specified period',
												'vk-dynamic-if-block'
											) }
											type={
												values.periodDisplaySetting ===
												'daysSincePublic'
													? 'number'
													: 'datetime-local'
											}
											step={
												values.periodDisplaySetting ===
												'daysSincePublic'
													? 1
													: 60
											}
											value={
												values.periodDisplayValue || ''
											}
											onChange={ ( value ) =>
												updateValue(
													'periodDisplayValue',
													value
												)
											}
										/>
									) }
									{ values.periodSpecificationMethod ===
										'referCustomField' && (
										<>
											<TextControl
												label={ __(
													'Referenced custom field name',
													'vk-dynamic-if-block'
												) }
												value={
													values.periodReferCustomField ||
													''
												}
												onChange={ ( value ) =>
													updateValue(
														'periodReferCustomField',
														value
													)
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
				),
				loginUser: () => (
					<ToggleControl
						label={ __(
							'Displayed only for logged-in users.',
							'vk-dynamic-if-block'
						) }
						checked={ values.showOnlyLoginUser || false }
						onChange={ ( checked ) =>
							updateValue( 'showOnlyLoginUser', checked )
						}
					/>
				),
			};

			return renderers[ type ]?.() || null;
		};

		// 共通のラベル生成関数
		const generateLabelFromValues = (
			values = {},
			options = [],
			valueKey = '',
			useSimpleLabel = false
		) => {
			const selected = Array.isArray( values[ valueKey ] )
				? values[ valueKey ]
				: [];
			if ( ! selected.length || ! Array.isArray( options ) ) {
				return null;
			}

			return selected
				.map(
					( val ) =>
						options.find( ( o ) => o?.value === val )?.[
							useSimpleLabel ? 'simpleLabel' : 'label'
						] || val
				)
				.join( ', ' );
		};

		const generateLabels = () => {
			if ( ! Array.isArray( conditions ) || ! conditions.length ) {
				return exclusion
					? `${ __( '!', 'vk-dynamic-if-block' ) } ${ __(
							'No conditions set',
							'vk-dynamic-if-block'
					  ) }`
					: __( 'No conditions set', 'vk-dynamic-if-block' );
			}

			const groupLabels = conditions
				.map( ( group ) => {
					const { conditions: groupConditions = [] } = group || {};
					if ( ! groupConditions.length ) {
						return null;
					}

					const condition = groupConditions[ 0 ];
					if ( ! condition?.type ) {
						return null;
					}

					const { values = {} } = condition;
					const labelMap = {
						pageType: () =>
							generateLabelFromValues(
								values,
								ifPageTypes,
								'ifPageType',
								true
							),
						postType: () =>
							generateLabelFromValues(
								values,
								vkDynamicIfBlockLocalizeData?.postTypeSelectOptions ||
									[],
								'ifPostType'
							),
						language: () =>
							generateLabelFromValues(
								values,
								vkDynamicIfBlockLocalizeData?.languageSelectOptions ||
									[],
								'ifLanguage'
							),
						userRole: () =>
							generateLabelFromValues(
								values,
								userRoles,
								'userRole'
							),
						postAuthor: () =>
							generateLabelFromValues(
								values,
								userSelectOptions,
								'postAuthor'
							),
						customField: () => values.customFieldName || null,
						period: () =>
							values.periodDisplaySetting &&
							values.periodDisplaySetting !== 'none'
								? values.periodDisplaySetting
								: null,
						loginUser: () =>
							values.showOnlyLoginUser
								? __( 'Login User Only', 'vk-dynamic-if-block' )
								: null,
					};

					const label =
						labelMap[ condition.type ]?.() || condition.type;
					return label || null;
				} )
				.filter( Boolean );

			if ( ! groupLabels.length ) {
				return exclusion
					? `${ __( '!', 'vk-dynamic-if-block' ) } ${ __(
							'No conditions set',
							'vk-dynamic-if-block'
					  ) }`
					: __( 'No conditions set', 'vk-dynamic-if-block' );
			}

			// Condition 1つだけの場合はカッコなし、複数の場合はカッコ付き
			const labelsString =
				groupLabels.length === 1
					? groupLabels[ 0 ]
					: groupLabels
							.map( ( label ) => `[${ label }]` )
							.join(
								` ${
									conditionOperator?.toUpperCase() || 'AND'
								} `
							);

			return exclusion
				? `${ __( '!', 'vk-dynamic-if-block' ) } ${ labelsString }`
				: labelsString;
		};

		return (
			<div { ...useBlockProps( { className: BLOCK_CONFIG.className } ) }>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Display Conditions',
							'vk-dynamic-if-block'
						) }
						className={ 'vkdif' }
					>
						{ conditions.length === 0 ? (
							<div>
								<BaseControl
									__nextHasNoMarginBottom
									className="dynamic-if-add-condition"
								>
									<p>
										{ __(
											'No conditions set. Add a condition to control display.',
											'vk-dynamic-if-block'
										) }
									</p>
									<Button
										variant="primary"
										onClick={ addCondition }
									>
										{ __(
											'Add Condition',
											'vk-dynamic-if-block'
										) }
									</Button>
								</BaseControl>
								<ToggleControl
									label={ __(
										'Exclusion designation',
										'vk-dynamic-if-block'
									) }
									checked={ exclusion }
									onChange={ ( checked ) =>
										setAttributes( { exclusion: checked } )
									}
								/>
							</div>
						) : (
							<>
								{ conditions.map( ( group, groupIndex ) => (
									<div
										key={ group.id }
										className="vkdif__group"
									>
										<div className="vkdif__group-header">
											<span className="vkdif__group-number">
												Condition { groupIndex + 1 }
											</span>
										</div>
										<div className="vkdif__group-conditions">
											{ group.conditions.map(
												(
													condition,
													conditionIndex
												) => {
													// 他グループで選択済みのCondition Typeを取得
													const usedTypes = conditions
														.filter(
															( _, idx ) =>
																idx !==
																groupIndex
														)
														.map(
															( g ) =>
																g
																	.conditions[ 0 ]
																	?.type
														)
														.filter( Boolean );
													// 選択肢をdisabled付きで生成
													const availableConditionTypes =
														conditionTypes.map(
															( opt ) => ( {
																...opt,
																disabled:
																	usedTypes.includes(
																		opt.value
																	) &&
																	opt.value !==
																		condition.type,
															} )
														);
													return (
														<div
															key={ condition.id }
															className="vkdif__condition"
														>
															<div className="vkdif__condition-header">
																<SelectControl
																	label={ __(
																		'Condition Type',
																		'vk-dynamic-if-block'
																	) }
																	value={
																		condition.type
																	}
																	options={
																		availableConditionTypes
																	}
																	onChange={ (
																		value
																	) =>
																		updateCondition(
																			groupIndex,
																			conditionIndex,
																			{
																				type: value,
																				values: {},
																			}
																		)
																	}
																/>
															</div>
															<div className="vkdif__condition-settings">
																{ renderConditionSettings(
																	condition,
																	groupIndex,
																	conditionIndex
																) }
															</div>
														</div>
													);
												}
											) }
										</div>
										<Button
											variant="secondary"
											isDestructive
											onClick={ () => {
												const newConditions = [
													...conditions,
												];
												newConditions.splice(
													groupIndex,
													1
												);
												setAttributes( {
													conditions: newConditions,
												} );
											} }
										>
											{ __(
												'Remove Condition',
												'vk-dynamic-if-block'
											) }
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
										disabled={
											conditions.length >=
											Object.keys( CONDITION_TYPE_LABELS )
												.length
										}
									>
										{ __(
											'Add Condition',
											'vk-dynamic-if-block'
										) }
									</Button>
								</BaseControl>
								{ conditions.length >=
									Object.keys( CONDITION_TYPE_LABELS )
										.length && (
									<BaseControl>
										<div className="vkdif__alert vkdif__alert-warning">
											<p>
												{ __(
													'Maximum number of conditions reached. Each condition type can only be used once.',
													'vk-dynamic-if-block'
												) }
											</p>
										</div>
									</BaseControl>
								) }
								{ conditions.length > 1 && (
									<SelectControl
										label={ __(
											'Condition Operator',
											'vk-dynamic-if-block'
										) }
										value={ conditionOperator }
										options={ CONDITION_OPERATORS }
										onChange={ ( value ) =>
											setAttributes( {
												conditionOperator: value,
											} )
										}
									/>
								) }
								<ToggleControl
									label={ __(
										'Exclusion designation',
										'vk-dynamic-if-block'
									) }
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
						{ generateLabels() ||
							__( 'No conditions set', 'vk-dynamic-if-block' ) }
					</span>
				</div>

				<InnerBlocks template={ BLOCK_CONFIG.defaultTemplate } />
			</div>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},
} );
