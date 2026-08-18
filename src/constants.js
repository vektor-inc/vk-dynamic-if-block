import { __, _x } from '@wordpress/i18n';

// 条件タイプの定義
export const CONDITION_TYPE_LABELS = {
	pageType: _x( 'Page Type', 'Condition Type Label', 'vk-dynamic-if-block' ),
	postType: _x( 'Post Type', 'Condition Type Label', 'vk-dynamic-if-block' ),
	taxonomy: _x( 'Taxonomy', 'Condition Type Label', 'vk-dynamic-if-block' ),
	language: _x( 'Language', 'Condition Type Label', 'vk-dynamic-if-block' ),
	userRole: _x( 'User Role', 'Condition Type Label', 'vk-dynamic-if-block' ),
	postAuthor: _x(
		'Post Author',
		'Condition Type Label',
		'vk-dynamic-if-block'
	),
	customField: _x(
		'Custom Field',
		'Condition Type Label',
		'vk-dynamic-if-block'
	),
	period: _x(
		'Display Period',
		'Condition Type Label',
		'vk-dynamic-if-block'
	),
	loginUser: _x(
		'Login User Only',
		'Condition Type Label',
		'vk-dynamic-if-block'
	),
	mobileDevice: _x(
		'Device Type',
		'Condition Type Label',
		'vk-dynamic-if-block'
	),
};

// Device type options (No restriction / Mobile Devices Only / PC Only).
// Stored in values.showOnlyMobileDevice of the mobileDevice condition type.
// デバイス種類の選択肢（指定なし／モバイル端末のみ表示／PC表示のみ）
// mobileDevice 条件タイプの values.showOnlyMobileDevice に格納する値
export const MOBILE_DEVICE_OPTIONS = [
	{ value: 'none', label: __( 'No restriction', 'vk-dynamic-if-block' ) },
	{
		value: 'mobileOnly',
		label: __( 'Mobile Devices Only', 'vk-dynamic-if-block' ),
	},
	{
		value: 'pcOnly',
		label: __( 'PC Only', 'vk-dynamic-if-block' ),
	},
];

/**
 * Normalize the stored value of the mobileDevice condition (which can be either
 * a boolean or a string) into a string for SelectControl display.
 *
 * The old spec stored a boolean toggle (true/false), so the following mapping
 * is applied for backward compatibility.
 * - true             → 'mobileOnly' (old toggle ON = Mobile Devices Only)
 * - false / unset    → 'none' (old toggle OFF / unset = No restriction; never auto-converted to PC Only)
 *
 * mobileDevice 条件の保存値（真偽値／文字列いずれもあり得る）を
 * SelectControl 表示用の正規化した文字列に変換する。
 *
 * 旧仕様ではトグルの真偽値（true/false）で保存されていたため、
 * 後方互換のために以下のマッピングを行う。
 * - true          → 'mobileOnly'（旧トグルON = モバイル端末のみ表示）
 * - false／未設定 → 'none'（旧トグルOFF・未設定 = 指定なし。PC専用へは自動変換しない）
 *
 * @param {boolean|string|undefined} rawValue The stored value. / 保存されている値。
 * @return {string} One of 'none' | 'mobileOnly' | 'pcOnly'. / 'none' | 'mobileOnly' | 'pcOnly' のいずれか。
 */
export const normalizeMobileDeviceValue = ( rawValue ) => {
	if ( rawValue === true || rawValue === 'mobileOnly' ) {
		return 'mobileOnly';
	}
	if ( rawValue === 'pcOnly' ) {
		return 'pcOnly';
	}
	return 'none';
};

// ページタイプ定義
export const PAGE_TYPE_DEFINITIONS = [
	{ value: 'none', label: __( 'No restriction', 'vk-dynamic-if-block' ) },
	{
		value: 'is_front_page',
		label: __( 'Front Page', 'vk-dynamic-if-block' ),
		func: 'is_front_page()',
	},
	{
		value: 'is_single',
		label: __( 'Single', 'vk-dynamic-if-block' ),
		func: 'is_single()',
	},
	{
		value: 'is_page',
		label: __( 'Page', 'vk-dynamic-if-block' ),
		func: 'is_page()',
	},
	{
		value: 'is_singular',
		label: __( 'Singular', 'vk-dynamic-if-block' ),
		func: 'is_singular()',
	},
	{
		value: 'is_home',
		label: __( 'Post Top', 'vk-dynamic-if-block' ),
		func: 'is_home() && ! is_front_page()',
	},
	{
		value: 'is_post_type_archive',
		label: __( 'Post Type Archive', 'vk-dynamic-if-block' ),
		func: 'is_post_type_archive() && !is_year() && !is_month() && !is_date()',
	},
	{
		value: 'is_category',
		label: __( 'Category Archive', 'vk-dynamic-if-block' ),
		func: 'is_category()',
	},
	{
		value: 'is_tag',
		label: __( 'Tag Archive', 'vk-dynamic-if-block' ),
		func: 'is_tag()',
	},
	{
		value: 'is_tax',
		label: __( 'Taxonomy Archive', 'vk-dynamic-if-block' ),
		func: 'is_tax()',
	},
	{
		value: 'is_year',
		label: __( 'Yearly Archive', 'vk-dynamic-if-block' ),
		func: 'is_year()',
	},
	{
		value: 'is_month',
		label: __( 'Monthly Archive', 'vk-dynamic-if-block' ),
		func: 'is_month()',
	},
	{
		value: 'is_date',
		label: __( 'Daily Archive', 'vk-dynamic-if-block' ),
		func: 'is_date()',
	},
	{
		value: 'is_author',
		label: __( 'Author Archive', 'vk-dynamic-if-block' ),
		func: 'is_author()',
	},
	{
		value: 'is_archive',
		label: __( 'Archive', 'vk-dynamic-if-block' ),
		func: 'is_archive()',
	},
	{
		value: 'is_search',
		label: __( 'Search Result', 'vk-dynamic-if-block' ),
		func: 'is_search()',
	},
	{
		value: 'is_404',
		label: __( '404', 'vk-dynamic-if-block' ),
		func: 'is_404()',
	},
];

// カスタムフィールドルール
export const CUSTOM_FIELD_RULES = [
	{
		value: 'valueExists',
		label: __( 'Value Exist ( !empty() )', 'vk-dynamic-if-block' ),
	},
	{
		value: 'valueEquals',
		label: __( 'Value Equals ( === )', 'vk-dynamic-if-block' ),
	},
];

// 期間設定
export const PERIOD_SETTINGS = [
	{ value: 'none', label: __( 'No restriction', 'vk-dynamic-if-block' ) },
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
		label: __(
			'Number of days from the date of publication',
			'vk-dynamic-if-block'
		),
	},
];

// 期間指定方法
export const PERIOD_METHODS = [
	{
		value: 'direct',
		label: __( 'Direct input in this block', 'vk-dynamic-if-block' ),
	},
	{
		value: 'referCustomField',
		label: __( 'Refer to value of custom field', 'vk-dynamic-if-block' ),
	},
];

// ページ階層
export const PAGE_HIERARCHY_OPTIONS = [
	{ value: 'none', label: __( 'No restriction', 'vk-dynamic-if-block' ) },
	{
		value: 'has_parent',
		label: __( 'Has parent page', 'vk-dynamic-if-block' ),
	},
	{
		value: 'has_children',
		label: __( 'Has child pages', 'vk-dynamic-if-block' ),
	},
];

// 条件演算子
export const CONDITION_OPERATORS = [
	{ label: __( 'AND', 'vk-dynamic-if-block' ), value: 'and' },
	{ label: __( 'OR', 'vk-dynamic-if-block' ), value: 'or' },
];

// ブロック設定
export const BLOCK_CONFIG = {
	className: 'vk-dynamic-if-block',
	defaultTemplate: [ [ 'core/paragraph', {} ] ],
	defaultConditionType: 'pageType',
	defaultOperator: 'and',
};

// 移行ルール定義
export const createMigrationRules = ( attributes ) => [
	{
		attr: 'ifPageType',
		type: 'pageType',
		key: 'ifPageType',
		condition: ( val ) => val && val !== 'none',
	},
	{
		attr: 'ifPostType',
		type: 'postType',
		key: 'ifPostType',
		condition: ( val ) => val && val !== 'none',
	},
	{
		attr: 'ifLanguage',
		type: 'language',
		key: 'ifLanguage',
		condition: ( val ) => val && val !== 'none',
	},
	{
		attr: 'userRole',
		type: 'userRole',
		key: 'userRole',
		condition: ( val ) => val && val.length > 0,
	},
	{
		attr: 'postAuthor',
		type: 'postAuthor',
		key: 'postAuthor',
		condition: ( val ) => val && val > 0,
	},
	{
		attr: 'customFieldName',
		type: 'customField',
		key: null,
		condition: ( val ) => val,
		customValues: () => ( {
			customFieldName: attributes.customFieldName,
			...( attributes.customFieldRule
				? { customFieldRule: attributes.customFieldRule }
				: {} ),
			...( attributes.customFieldValue
				? { customFieldValue: attributes.customFieldValue }
				: {} ),
		} ),
	},
	{
		attr: 'periodDisplaySetting',
		type: 'period',
		key: null,
		condition: ( val ) => val && val !== 'none',
		customValues: () => ( {
			periodDisplaySetting: attributes.periodDisplaySetting,
			...( attributes.periodSpecificationMethod
				? {
						periodSpecificationMethod:
							attributes.periodSpecificationMethod,
				  }
				: {} ),
			...( attributes.periodDisplayValue
				? { periodDisplayValue: attributes.periodDisplayValue }
				: {} ),
			...( attributes.periodReferCustomField
				? { periodReferCustomField: attributes.periodReferCustomField }
				: {} ),
		} ),
	},
	{
		attr: 'showOnlyLoginUser',
		type: 'loginUser',
		key: 'showOnlyLoginUser',
		condition: ( val ) => val,
	},
	{
		attr: 'showOnlyMobileDevice',
		type: 'mobileDevice',
		key: 'showOnlyMobileDevice',
		// 'none'（指定なし）は制限しないため移行対象にしない
		// 'none' means "no restriction", so it is not migrated.
		condition: ( val ) => val && val !== 'none',
	},
];

/**
 * Build the condition values for a migration rule.
 * Array values (such as userRole) are passed through as they are, because both the
 * PHP evaluation and the editor UI accept an array of values.
 *
 * 移行ルールから条件の値を組み立てる。
 * userRole のような配列の値は、PHP の判定もエディタの UI も配列を受け付けるため、
 * 先頭要素へ切り詰めずそのまま渡す。
 *
 * @param {Object} rule       Migration rule created by createMigrationRules(). / createMigrationRules() が返す移行ルール。
 * @param {Object} attributes Block attributes. / ブロックの属性。
 * @return {Object} Values for the migrated condition. / 移行後の条件に設定する値。
 */
export const buildMigrationValues = ( rule, attributes ) => {
	if ( rule.customValues ) {
		return rule.customValues();
	}
	return { [ rule.key ]: attributes[ rule.attr ] };
};

/**
 * Default value used when an old attribute is cleared.
 * 旧属性をクリアするときの既定値。
 */
export const OLD_ATTRIBUTE_CLEARED_VALUES = {
	ifPageType: 'none',
	ifPostType: 'none',
	ifLanguage: 'none',
	userRole: [],
	postAuthor: 0,
	// 属性宣言上の既定値が空文字のものは空文字に戻す（保存内容から消えるようにするため）
	// The attributes whose declared default is an empty string are reset to it, so
	// that they disappear from the saved markup.
	customFieldName: '',
	customFieldRule: 'valueExists',
	customFieldValue: '',
	periodDisplaySetting: 'none',
	periodSpecificationMethod: 'direct',
	periodDisplayValue: '',
	periodReferCustomField: '',
	showOnlyLoginUser: false,
	showOnlyMobileDevice: false,
};

/**
 * Whether the old attribute actually holds a value that has to be migrated.
 * Attributes that only hold their own default value are not treated as set, so that
 * simply opening a block does not mark the post as modified.
 *
 * 旧属性に移行すべき値が実際に入っているかを判定する。
 * 既定値のままの属性は「設定されていない」として扱い、ブロックを開いただけで
 * 投稿が変更済みになることを防ぐ。
 *
 * @param {string} attr  Attribute name. / 属性名。
 * @param {*}      value Attribute value. / 属性の値。
 * @return {boolean} True when the attribute holds a value to migrate. / 移行すべき値が入っている場合は true。
 */
export const isOldAttributeSet = ( attr, value ) => {
	if ( attr === 'userRole' ) {
		return Array.isArray( value ) && value.length > 0;
	}
	if ( attr === 'postAuthor' ) {
		return !! value && value !== 0;
	}
	if ( attr === 'showOnlyLoginUser' || attr === 'showOnlyMobileDevice' ) {
		return value === true;
	}
	// これらの属性は既定値が 'none' や空文字ではないため、既定値と異なる場合のみ設定済みとみなす
	// These attributes do not default to 'none' or an empty string, so they are only
	// treated as set when they differ from their own default value.
	if ( attr === 'customFieldRule' ) {
		return !! value && value !== 'valueExists' && value !== 'none';
	}
	if ( attr === 'periodSpecificationMethod' ) {
		return !! value && value !== 'direct' && value !== 'none';
	}
	return !! value && value !== 'none' && value !== '';
};

/**
 * Whether the attribute value already equals the value used when it is cleared.
 * 属性の値が、クリア時に設定する値と既に同じかどうか。
 *
 * @param {string} attr  Attribute name. / 属性名。
 * @param {*}      value Attribute value. / 属性の値。
 * @return {boolean} True when the attribute does not need to be cleared. / クリアが不要な場合は true。
 */
export const isOldAttributeCleared = ( attr, value ) => {
	const clearedValue = OLD_ATTRIBUTE_CLEARED_VALUES[ attr ];
	if ( Array.isArray( clearedValue ) ) {
		return Array.isArray( value ) && value.length === 0;
	}
	// 空値・未設定は「指定なし」なのでクリア済みとして扱う
	// An empty or unset value means "not specified", so it is already cleared.
	if ( ! value ) {
		return true;
	}
	if ( clearedValue === false ) {
		return value !== true;
	}
	return value === clearedValue || value === 'none';
};

/**
 * Whether an existing condition actually restricts the display for the migrated one.
 * A condition row that was just added holds no value yet ( for example
 * { type: 'pageType', values: {} } ) and is evaluated as "no restriction" by PHP, so
 * only a condition of the same type that really restricts the display counts as the
 * destination of the migration.
 *
 * 既存の条件が、移行しようとしている条件に対して実際に表示を制限しているかを判定する。
 * 追加した直後の条件行は値を持たず（例: { type: 'pageType', values: {} } ）、PHP では
 * 「指定なし」として評価されるため、同じ種類で実際に表示を制限している条件だけを
 * 移行先とみなす。
 *
 * @param {Object} condition Existing condition. / 既存の条件。
 * @param {Object} migrated  Condition to migrate, with its migration rule. / 移行しようとしている条件（移行ルール付き）。
 * @return {boolean} True when the existing condition restricts the display. / 実際に表示を制限している場合は true。
 */
const isConditionRestricting = ( condition, migrated ) => {
	if ( condition?.type !== migrated.type ) {
		return false;
	}

	const values = condition?.values || {};
	const rule = migrated.rule;

	if ( rule?.key ) {
		const value = values[ rule.key ];
		// 'none' は「指定なし」なので制限していない
		// 'none' means "no restriction".
		if ( value === 'none' ) {
			return false;
		}
		return !! rule.condition( value );
	}

	// key を持たないルール（複数の値をまとめて移行するもの）は代表のキーで判定する。
	// 未知の種類は「制限していない」として扱い、差し込む側（安全側）に倒す
	// The rules without a key migrate several values at once, so the representative
	// key is used instead. An unknown type is treated as not restricting, which
	// keeps the migrated condition inserted ( the safe side ).
	if ( migrated.type === 'customField' ) {
		return !! values.customFieldName && values.customFieldName !== 'none';
	}
	if ( migrated.type === 'period' ) {
		return (
			!! values.periodDisplaySetting &&
			values.periodDisplaySetting !== 'none'
		);
	}
	return false;
};

/**
 * Insert the migrated conditions into the groups that do not restrict the display
 * with them yet.
 * Conditions inside a group are combined with AND while groups are combined with
 * conditionOperator, so inserting into every group keeps the restriction for both
 * operators ( (A and L) or (B and L) = (A or B) and L ). A group that already holds
 * a restricting condition of the same type is left untouched even when its value
 * differs, because a duplicated condition of the same type would restrict the display
 * more than the visible setting does. That trade-off only holds while the existing
 * condition really restricts the display: inserting into a condition row that
 * restricts nothing evaluates as ( true and L ) = L, so it never narrows the display
 * more than intended. The check is made per group, since a condition held by another
 * group does not restrict this group at all.
 *
 * 移行した条件を、まだその条件で表示を制限していないグループへ差し込む。
 * グループ内は AND 結合、グループ同士は conditionOperator で結合されるため、各グループへ
 * 差し込めばどちらの演算子でも制限が保たれる（ (A∧L)∨(B∧L) = (A∨B)∧L ）。
 * 同じ種類で実際に制限している条件を既に持つグループは、値が違ってもそのままにする。
 * グループ内は AND 結合のため、同種の条件を重複させると画面上の設定より表示が絞られて
 * しまうからで、この割り切りは既存の条件が実際に制限を掛けている場合にだけ成り立つ。
 * 制限していない条件行へ差し込んだ場合は (true ∧ L) = L となり、絞りすぎにはならない。
 * 判定はグループ単位で行う。別のグループが持つ条件は、このグループを何ら制限しないため。
 *
 * @param {Array} conditions         Condition groups. / 条件グループ。
 * @param {Array} migratedConditions Conditions to insert, with their migration rules. / 差し込む条件（移行ルール付き）。
 * @return {Array|null} New condition groups, or null when nothing was inserted. / 新しい条件グループ。差し込みが無い場合は null。
 */
export const insertMigratedConditions = ( conditions, migratedConditions ) => {
	let inserted = false;

	const newConditions = conditions.map( ( group ) => {
		const groupConditions = group?.conditions || [];
		const added = migratedConditions.filter(
			( migrated ) =>
				! groupConditions.some( ( condition ) =>
					isConditionRestricting( condition, migrated )
				)
		);

		if ( added.length === 0 ) {
			return group;
		}

		inserted = true;

		return {
			...group,
			conditions: [
				...groupConditions,
				...added.map( ( migrated ) => ( {
					id: generateId(),
					type: migrated.type,
					values: { ...migrated.values },
				} ) ),
			],
		};
	} );

	return inserted ? newConditions : null;
};

// ユーティリティ関数
export const generateId = () => {
	return (
		'vkdif_' +
		Math.random().toString( 36 ).substr( 2, 15 ) +
		'_' +
		Date.now().toString( 36 )
	);
};

export const createConditionGroup = ( type, values ) => ( {
	id: generateId(),
	conditions: [ { id: generateId(), type, values } ],
	operator: BLOCK_CONFIG.defaultOperator,
} );

// 言語ソート関数
export const sortLanguages = ( languages = [], currentSiteLanguage = '' ) => {
	return [ ...languages ].sort( ( a, b ) => {
		if ( a.value === '' ) {
			return -1;
		}
		if ( b.value === '' ) {
			return 1;
		}
		if ( a.value === currentSiteLanguage ) {
			return -1;
		}
		if ( b.value === currentSiteLanguage ) {
			return 1;
		}
		if ( a.value === 'en_US' ) {
			return -1;
		}
		if ( b.value === 'en_US' ) {
			return 1;
		}
		return a.label.localeCompare( b.label );
	} );
};
