import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
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
import {
	CONDITION_TYPE_LABELS,
	PAGE_TYPE_DEFINITIONS,
	CUSTOM_FIELD_RULES,
	PERIOD_SETTINGS,
	PERIOD_METHODS,
	PAGE_HIERARCHY_OPTIONS,
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
		useEffect( () => {
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
										? value[ 0 ] || ''
										: value,
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
							{ ifPageType: 'none' },
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
					conditions: [ newCondition ],
					operator: BLOCK_CONFIG.defaultOperator,
				} );
			} else {
				newConditions[ 0 ].conditions.push( newCondition );
			}

			setAttributes( { conditions: newConditions } );
		};

		const addConditionGroup = () => {
			// デフォルトの条件タイプを使用
			const firstType = BLOCK_CONFIG.defaultConditionType;

			// デフォルト値を設定
			let defaultValues = {};
			if ( firstType === 'pageType' ) {
				defaultValues = { ifPageType: 'none' };
			} else if ( firstType === 'postType' ) {
				defaultValues = { ifPostType: 'none' };
			} else if ( firstType === 'userRole' ) {
				defaultValues = { userRole: [] };
			} else if ( firstType === 'language' ) {
				defaultValues = { ifLanguage: '' };
			} else if ( firstType === 'postAuthor' ) {
				defaultValues = { postAuthor: 0 };
			}

			const newConditionGroup = {
				id: generateId(),
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

		const renderConditionSettings = (
			condition = {},
			groupIndex = 0,
			conditionIndex = 0
		) => {
			const { type = '', values = {} } = condition;
			const updateValue = ( key, value ) =>
				updateConditionValue( groupIndex, conditionIndex, key, value );

			// 共通の階層条件
			const renderPageHierarchy = () => (
				<SelectControl
					label={ __( 'Page Hierarchy', 'vk-dynamic-if-block' ) }
					value={ values.pageHierarchyType || 'none' }
					options={ PAGE_HIERARCHY_OPTIONS }
					onChange={ ( value ) =>
						updateValue( 'pageHierarchyType', value )
					}
				/>
			);

			const renderers = {
				pageType: () => (
					<>
						<SelectControl
							label={ __( 'Page Type', 'vk-dynamic-if-block' ) }
							value={ values.ifPageType || 'none' }
							options={ ifPageTypes }
							onChange={ ( value ) =>
								updateValue( 'ifPageType', value )
							}
						/>
						{ values.ifPageType === 'is_page' &&
							renderPageHierarchy() }
					</>
				),
				postType: () => (
					<>
						<SelectControl
							label={ __( 'Post Type', 'vk-dynamic-if-block' ) }
							value={ values.ifPostType || 'none' }
							options={
								vkDynamicIfBlockLocalizeData?.postTypeSelectOptions ||
								[]
							}
							onChange={ ( value ) =>
								updateValue( 'ifPostType', value )
							}
						/>
						{ values.ifPostType === 'page' &&
							renderPageHierarchy() }
					</>
				),
				language: () => {
					const allLanguages =
						vkDynamicIfBlockLocalizeData?.languageSelectOptions ||
						[];
					const currentSiteLanguage =
						vkDynamicIfBlockLocalizeData?.currentSiteLanguage || '';
					const sortedLanguages = sortLanguages(
						allLanguages,
						currentSiteLanguage
					);

					return (
						<SelectControl
							label={ __( 'Language', 'vk-dynamic-if-block' ) }
							value={ values.ifLanguage || '' }
							options={ sortedLanguages }
							onChange={ ( value ) =>
								updateValue( 'ifLanguage', value )
							}
						/>
					);
				},
				userRole: () => (
					<BaseControl
						__nextHasNoMarginBottom
						className="dynamic-if-user-role"
					>
						{ userRoles.map( ( role, index ) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ role?.value || index }
								label={ role?.label || '' }
								checked={ ( values.userRole || [] ).includes(
									role.value
								) }
								onChange={ ( checked ) => {
									const currentRoles = values.userRole || [];
									const newRoles = checked
										? [ ...currentRoles, role.value ]
										: currentRoles.filter(
												( r ) => r !== role.value
										  );
									updateValue( 'userRole', newRoles );
								} }
							/>
						) ) }
					</BaseControl>
				),
				postAuthor: () => (
					<SelectControl
						label={ __( 'Post Author', 'vk-dynamic-if-block' ) }
						value={ values.postAuthor || 0 }
						options={ userSelectOptions }
						onChange={ ( value ) =>
							updateValue( 'postAuthor', parseInt( value ) || 0 )
						}
					/>
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
			const value = values[ valueKey ];
			if ( ! value || ! Array.isArray( options ) ) {
				return null;
			}

			const option = options.find( ( o ) => o?.value === value );
			return (
				option?.[ useSimpleLabel ? 'simpleLabel' : 'label' ] || value
			);
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
						pageType: () => {
							const pageTypeLabel = generateLabelFromValues(
								values,
								ifPageTypes,
								'ifPageType',
								true
							);
							const hierarchyLabel =
								values.ifPageType === 'is_page' &&
								values.pageHierarchyType &&
								values.pageHierarchyType !== 'none'
									? generateLabelFromValues(
											values,
											PAGE_HIERARCHY_OPTIONS,
											'pageHierarchyType'
									  )
									: null;
							return hierarchyLabel
								? `${ pageTypeLabel } (${ hierarchyLabel })`
								: pageTypeLabel;
						},
						postType: () => {
							const postTypeLabel = generateLabelFromValues(
								values,
								vkDynamicIfBlockLocalizeData?.postTypeSelectOptions ||
									[],
								'ifPostType'
							);
							const hierarchyLabel =
								values.ifPostType === 'page' &&
								values.pageHierarchyType &&
								values.pageHierarchyType !== 'none'
									? generateLabelFromValues(
											values,
											PAGE_HIERARCHY_OPTIONS,
											'pageHierarchyType'
									  )
									: null;
							return hierarchyLabel
								? `${ postTypeLabel } (${ hierarchyLabel })`
								: postTypeLabel;
						},
						language: () =>
							generateLabelFromValues(
								values,
								vkDynamicIfBlockLocalizeData?.languageSelectOptions ||
									[],
								'ifLanguage'
							),
						userRole: () => {
							const selectedRoles = values.userRole || [];
							if ( ! selectedRoles.length ) {
								return null;
							}
							return selectedRoles
								.map(
									( role ) =>
										userRoles.find(
											( r ) => r.value === role
										)?.label || role
								)
								.join( ', ' );
						},
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

			// 各Conditionのラベルを結合
			const labelsString = groupLabels.join(
				` ${ conditionOperator?.toUpperCase() || 'AND' } `
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
										<div className="vkdif__group-conditions">
											{ group.conditions.map(
												(
													condition,
													conditionIndex
												) => {
													// 全ての条件タイプを選択可能
													const availableConditionTypes =
														conditionTypes;
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
									>
										{ __(
											'Add Condition',
											'vk-dynamic-if-block'
										) }
									</Button>
								</BaseControl>
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
