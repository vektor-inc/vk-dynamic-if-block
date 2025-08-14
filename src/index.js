import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import { useEffect, useMemo, useState } from '@wordpress/element';
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
					id: '',
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

		// 条件を更新する共通関数
		const updateConditionAt = ( groupIndex, conditionIndex, updater ) => {
			const newConditions = [ ...conditions ];
			newConditions[ groupIndex ] = {
				...newConditions[ groupIndex ],
				conditions: [ ...newConditions[ groupIndex ].conditions ],
			};

			const oldCondition =
				newConditions[ groupIndex ].conditions[ conditionIndex ];
			const updatedCondition = updater( oldCondition );
			newConditions[ groupIndex ].conditions[ conditionIndex ] =
				updatedCondition;
			setAttributes( { conditions: newConditions } );
		};

		// 空のIDを生成する（ブロック複製対策）
		useEffect( () => {
			if ( conditions && conditions.length > 0 ) {
				const hasEmptyIds = conditions.some(
					( group ) =>
						! group.id ||
						( group.conditions &&
							group.conditions.some(
								( condition ) => ! condition.id
							) )
				);

				if ( hasEmptyIds ) {
					const newConditions = conditions.map( ( group ) => ( {
						...group,
						id: group.id || generateId(),
						conditions: group.conditions
							? group.conditions.map( ( condition ) => ( {
									...condition,
									id: condition.id || generateId(),
							  } ) )
							: [],
					} ) );
					setAttributes( { conditions: newConditions } );
				}
			}
		}, [ conditions, setAttributes ] );

		// 既存ブロックから新形式への移行処理
		const [ hasMigrated, setHasMigrated ] = useState( false );

		useEffect( () => {
			// 既に移行済みの場合はスキップ
			if ( hasMigrated ) {
				return;
			}

			// 新しい形式が既に存在する場合は移行不要
			if (
				conditions &&
				conditions.length > 0 &&
				conditions[ 0 ] &&
				conditions[ 0 ].conditions.length > 0
			) {
				setHasMigrated( true );
				return;
			}

			// 古い属性が存在するかチェック
			const oldAttributes = [
				'ifPageType',
				'ifPostType',
				'ifLanguage',
				'userRole',
				'postAuthor',
				'customFieldName',
				'customFieldRule',
				'customFieldValue',
				'periodDisplaySetting',
				'periodSpecificationMethod',
				'periodDisplayValue',
				'periodReferCustomField',
				'showOnlyLoginUser',
			];

			const hasOldAttributes = oldAttributes.some( ( attr ) => {
				const value = attributes[ attr ];
				if ( attr === 'userRole' ) {
					return Array.isArray( value ) && value.length > 0;
				}
				if ( attr === 'postAuthor' ) {
					return value !== 0;
				}
				if ( attr === 'showOnlyLoginUser' ) {
					return value === true;
				}
				return value && value !== 'none' && value !== '';
			} );

			if ( ! hasOldAttributes ) {
				setHasMigrated( true );
				return;
			}

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
						createConditionGroup( rule.type, values, groupIndex++ )
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

			// 新しいconditionsを設定し、古い属性をクリア
			const attributesToUpdate = { conditions: newConditions };

			// 古い属性をクリア
			oldAttributes.forEach( ( attr ) => {
				let defaultValue = 'none';
				if ( attr === 'userRole' ) {
					defaultValue = [];
				} else if ( attr === 'postAuthor' ) {
					defaultValue = 0;
				} else if ( attr === 'showOnlyLoginUser' ) {
					defaultValue = false;
				} else if ( attr === 'customFieldRule' ) {
					defaultValue = 'valueExists';
				} else if ( attr === 'periodSpecificationMethod' ) {
					defaultValue = 'direct';
				}
				attributesToUpdate[ attr ] = defaultValue;
			} );

			setAttributes( attributesToUpdate );
			setHasMigrated( true );
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ hasMigrated ] );

		// 新しい形式を使用している場合に古い属性をクリア（移行後のみ実行）
		useEffect( () => {
			if ( ! hasMigrated ) {
				return;
			}

			if (
				conditions &&
				conditions.length > 0 &&
				conditions[ 0 ] &&
				conditions[ 0 ].conditions.length > 0
			) {
				// 新しい形式が使用されている場合、古い属性をクリア
				const oldAttributesToClear = [
					'ifPageType',
					'ifPostType',
					'ifLanguage',
					'userRole',
					'postAuthor',
					'customFieldName',
					'customFieldRule',
					'customFieldValue',
					'periodDisplaySetting',
					'periodSpecificationMethod',
					'periodDisplayValue',
					'periodReferCustomField',
					'showOnlyLoginUser',
					'showOnlyMobileDevice'
				];

				const hasOldAttributes = oldAttributesToClear.some(
					( attr ) => {
						const value = attributes[ attr ];
						if ( attr === 'userRole' ) {
							return Array.isArray( value ) && value.length > 0;
						}
						if ( attr === 'postAuthor' ) {
							return value !== 0;
						}
						if ( attr === 'showOnlyLoginUser' ) {
							return value === true;
						}
						return value && value !== 'none' && value !== '';
					}
				);

				if ( hasOldAttributes ) {
					const attributesToUpdate = {};
					oldAttributesToClear.forEach( ( attr ) => {
						let defaultValue = 'none';
						if ( attr === 'userRole' ) {
							defaultValue = [];
						} else if ( attr === 'postAuthor' ) {
							defaultValue = 0;
						} else if ( attr === 'showOnlyLoginUser' ) {
							defaultValue = false;
						} else if ( attr === 'customFieldRule' ) {
							defaultValue = 'valueExists';
						} else if ( attr === 'periodSpecificationMethod' ) {
							defaultValue = 'direct';
						}
						attributesToUpdate[ attr ] = defaultValue;
					} );
					setAttributes( attributesToUpdate );
				}
			}
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ hasMigrated, conditions ] );

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

		const userRoles = useMemo( () => {
			try {
				const result = Object.entries(
					vkDynamicIfBlockLocalizeData?.userRoles || {}
				).map( ( [ key, label ] ) => ( {
					value: key,
					// eslint-disable-next-line @wordpress/i18n-no-variables
					label: __( label, 'vk-dynamic-if-block' ),
				} ) );
				return result;
			} catch ( error ) {
				return [];
			}
		}, [] );

		const userSelectOptions = useMemo( () => {
			return vkDynamicIfBlockLocalizeData?.userSelectOptions || [];
		}, [] );

		const addCondition = () => {
			const newCondition = {
				id: generateId(),
				type: BLOCK_CONFIG.defaultConditionType,
				values: {},
			};

			if ( conditions.length === 0 ) {
				setAttributes( {
					conditions: [
						{
							id: generateId(),
							conditions: [ newCondition ],
							operator: BLOCK_CONFIG.defaultOperator,
						},
					],
				} );
			} else {
				const newConditions = [ ...conditions ];
				newConditions[ 0 ] = {
					...newConditions[ 0 ],
					conditions: [
						...newConditions[ 0 ].conditions,
						newCondition,
					],
				};
				setAttributes( { conditions: newConditions } );
			}
		};

		const addConditionGroup = () => {
			const newConditionGroup = {
				id: generateId(),
				conditions: [
					{
						id: generateId(),
						type: BLOCK_CONFIG.defaultConditionType,
						values: {},
					},
				],
				operator: 'or',
			};

			const newConditions = [ ...conditions, newConditionGroup ];
			setAttributes( {
				conditions: newConditions,
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

			updateConditionAt( groupIndex, conditionIndex, ( condition ) => {
				// 条件タイプが変更された場合、適切なデフォルト値を設定
				let newValues = condition.values;
				if ( updates.type && updates.type !== condition.type ) {
					if ( updates.type === 'pageType' ) {
						newValues = { ifPageType: 'none' };
					} else if ( updates.type === 'postType' ) {
						newValues = { ifPostType: 'none' };
					} else if ( updates.type === 'userRole' ) {
						newValues = { userRole: [] };
					} else if ( updates.type === 'language' ) {
						newValues = { ifLanguage: '' };
					} else if ( updates.type === 'postAuthor' ) {
						newValues = { postAuthor: 0 };
					} else if ( updates.type === 'taxonomy' ) {
						newValues = {
							taxonomy: 'none',
							termIds: [],
						};
					} else {
						newValues = {};
					}
				}

				return {
					...condition,
					...updates,
					values: newValues,
				};
			} );
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

			updateConditionAt( groupIndex, conditionIndex, ( condition ) => ( {
				...condition,
				values: {
					...condition.values,
					[ key ]: value,
				},
			} ) );
		};

		const renderConditionSettings = (
			condition = {},
			groupIndex = 0,
			conditionIndex = 0
		) => {
			const { type = '', values = {} } = condition;
			const updateValue = ( key, value ) =>
				updateConditionValue( groupIndex, conditionIndex, key, value );

			// 共通のパネル設定
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
							label={ _x( 'Language', 'Select a language', 'vk-dynamic-if-block' ) }
							value={ values.ifLanguage || '' }
							options={ sortedLanguages }
							onChange={ ( value ) =>
								updateValue( 'ifLanguage', value )
							}
						/>
					);
				},
				userRole: () => {
					try {
						// valuesが無効な場合は何も表示しない
						if ( ! values ) {
							return (
								<BaseControl
									__nextHasNoMarginBottom
									className="dynamic-if-user-role"
								>
									<p>
										{ __(
											'No values available',
											'vk-dynamic-if-block'
										) }
									</p>
								</BaseControl>
							);
						}

						// userRolesが配列でない場合は空配列を使用
						const roles = Array.isArray( userRoles )
							? userRoles
							: [];
						// values.userRoleが配列でない場合は配列に変換
						let currentUserRoles = [];
						if ( Array.isArray( values.userRole ) ) {
							currentUserRoles = values.userRole;
						} else if ( values.userRole ) {
							currentUserRoles = [ values.userRole ];
						}

						// rolesが空の場合は何も表示しない
						if ( roles.length === 0 ) {
							return (
								<BaseControl
									__nextHasNoMarginBottom
									className="dynamic-if-user-role"
								>
									<p>
										{ __(
											'No user roles available',
											'vk-dynamic-if-block'
										) }
									</p>
								</BaseControl>
							);
						}

						return (
							<BaseControl
								__nextHasNoMarginBottom
								className="dynamic-if-user-role"
							>
								{ roles.map( ( role, index ) => {
									// roleが無効な場合はスキップ
									if ( ! role || ! role.value ) {
										return (
											<div
												key={ `empty-${ index }` }
											></div>
										);
									}

									// role.valueが無効な場合はスキップ
									if (
										! role.value ||
										typeof role.value !== 'string'
									) {
										return (
											<div
												key={ `invalid-${ index }` }
											></div>
										);
									}

									return (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={ role.value || index }
											label={ role.label || '' }
											checked={ currentUserRoles.includes(
												role.value
											) }
											onChange={ ( checked ) => {
												const newRoles = checked
													? [
															...currentUserRoles,
															role.value,
													  ]
													: currentUserRoles.filter(
															( r ) =>
																r !== role.value
													  );
												updateValue(
													'userRole',
													newRoles
												);
											} }
										/>
									);
								} ) }
							</BaseControl>
						);
					} catch ( error ) {
						return (
							<BaseControl
								__nextHasNoMarginBottom
								className="dynamic-if-user-role"
							>
								<p>
									{ __(
										'Error loading user roles',
										'vk-dynamic-if-block'
									) }
								</p>
							</BaseControl>
						);
					}
				},
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
				taxonomy: () => {
					const taxonomies =
						vkDynamicIfBlockLocalizeData?.taxonomySelectOptions ||
						[];
					const terms =
						vkDynamicIfBlockLocalizeData?.termSelectOptions || {};
					const selectedTaxonomy = values.taxonomy || 'none';
					const availableTerms =
						selectedTaxonomy && selectedTaxonomy !== 'none'
							? terms[ selectedTaxonomy ] || []
							: [];
					const selectedTerms = Array.isArray( values.termIds )
						? values.termIds
						: [];

					return (
						<>
							<SelectControl
								label={ __(
									'Taxonomy',
									'vk-dynamic-if-block'
								) }
								value={ selectedTaxonomy }
								options={ [
									{
										value: 'none',
										label: __(
											'No restriction',
											'vk-dynamic-if-block'
										),
									},
									...taxonomies,
								] }
								onChange={ ( value ) => {
									// タクソノミーが変更されたらタームIDも同時にクリア
									updateConditionAt(
										groupIndex,
										conditionIndex,
										( currentCondition ) => ( {
											...currentCondition,
											values: {
												...currentCondition.values,
												taxonomy: value,
												termIds: [],
											},
										} )
									);
								} }
							/>
							{ selectedTaxonomy &&
								selectedTaxonomy !== 'none' && (
									<SelectControl
										label={ __(
											'Select Term',
											'vk-dynamic-if-block'
										) }
										value={ selectedTerms[ 0 ] || '' }
										options={ [
											{
												value: '',
												label: __(
													'No restriction',
													'vk-dynamic-if-block'
												),
											},
											...availableTerms,
										] }
										onChange={ ( value ) => {
											// タームが変更されたら完全にリセット
											updateValue(
												'termIds',
												value ? [ value ] : []
											);
										} }
									/>
								) }
						</>
					);
				},
				mobileDevice: () => (
					<ToggleControl
						label={ __( 'Displayed only on mobile devices.', 'vk-dynamic-if-block' ) }
						checked={ values.showOnlyMobileDevice || false }
						onChange={ ( checked ) =>
							updateValue( 'showOnlyMobileDevice', checked )
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

		const generateLabels = useMemo( () => {
			try {
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
						const { conditions: groupConditions = [] } =
							group || {};
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
								try {
									const selectedRoles = values.userRole || [];
									// selectedRolesが配列でない場合は配列に変換
									let roles = [];
									if ( Array.isArray( selectedRoles ) ) {
										roles = selectedRoles;
									} else if ( selectedRoles ) {
										roles = [ selectedRoles ];
									}
									if ( ! roles.length ) {
										return __(
											'No user roles selected',
											'vk-dynamic-if-block'
										);
									}
									// userRolesが配列でない場合は空配列を使用
									let availableRoles = [];
									if ( Array.isArray( userRoles ) ) {
										availableRoles = userRoles;
									}
									const result = roles
										.map( ( role ) => {
											if ( ! role ) {
												return '';
											}
											const foundRole =
												availableRoles.find(
													( r ) => r.value === role
												);
											return foundRole?.label || role;
										} )
										.filter( Boolean )
										.join( ', ' );
									return (
										result ||
										__(
											'Unknown user roles',
											'vk-dynamic-if-block'
										)
									);
								} catch ( error ) {
									return __(
										'Error generating user role label',
										'vk-dynamic-if-block'
									);
								}
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
									? __(
											'Login User Only',
											'vk-dynamic-if-block'
									  )
									: null,
							taxonomy: () => {
								try {
									const taxonomy = values.taxonomy;
									const termIds = values.termIds || [];

									if ( ! taxonomy || taxonomy === 'none' ) {
										return null;
									}

									const taxonomies =
										vkDynamicIfBlockLocalizeData?.taxonomySelectOptions ||
										[];
									const terms =
										vkDynamicIfBlockLocalizeData?.termSelectOptions ||
										{};
									const taxonomyLabel =
										taxonomies.find(
											( t ) => t.value === taxonomy
										)?.label || taxonomy;
									const availableTerms =
										terms[ taxonomy ] || [];

									const selectedTermLabels = termIds
										.map( ( termId ) => {
											const term = availableTerms.find(
												( t ) =>
													t.value ===
													parseInt( termId )
											);
											return term?.label || '';
										} )
										.filter( Boolean )
										.join( ', ' );

									if ( ! selectedTermLabels ) {
										return `${ taxonomyLabel } (${ __(
											'No terms selected',
											'vk-dynamic-if-block'
										) })`;
									}

									return `${ taxonomyLabel }: ${ selectedTermLabels }`;
								} catch ( error ) {
									return __(
										'Error generating taxonomy label',
										'vk-dynamic-if-block'
									);
								}
							},
							showOnlyMobileDevice: () =>
								values.showOnlyMobileDevice
									? __( 'Mobile Device Only', 'vk-dynamic-if-block' )
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
			} catch ( error ) {
				return __( 'Error generating labels', 'vk-dynamic-if-block' );
			}
		}, [
			conditions,
			conditionOperator,
			exclusion,
			ifPageTypes,
			userRoles,
			userSelectOptions,
		] );

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
						{ generateLabels ||
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