import { __ } from '@wordpress/i18n';

// 条件タイプの定義
export const CONDITION_TYPE_LABELS = {
	pageType: 'Page Type',
	postType: 'Post Type',
	language: 'Language',
	userRole: 'User Role',
	postAuthor: 'Post Author',
	customField: 'Custom Field',
	period: 'Display Period',
	loginUser: 'Login User Only',
};

// ページタイプ定義
export const PAGE_TYPE_DEFINITIONS = [
	{ value: 'none', label: 'No restriction' },
	{ value: 'is_front_page', label: 'Front Page', func: 'is_front_page()' },
	{ value: 'is_single', label: 'Single', func: 'is_single()' },
	{ value: 'is_page', label: 'Page', func: 'is_page()' },
	{ value: 'is_singular', label: 'Singular', func: 'is_singular()' },
	{
		value: 'is_home',
		label: 'Post Top',
		func: 'is_home() && ! is_front_page()',
	},
	{
		value: 'is_post_type_archive',
		label: 'Post Type Archive',
		func: 'is_post_type_archive() && !is_year() && !is_month() && !is_date()',
	},
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
	{ label: 'AND', value: 'and' },
	{ label: 'OR', value: 'or' },
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
		condition: ( val ) => val && ( Array.isArray( val ) ? val.length > 0 : val !== 'none' ),
		customValues: () => {
			const userRoleValue = attributes.userRole;
			// 文字列の場合は配列に変換
			const userRoleArray = Array.isArray( userRoleValue ) ? userRoleValue : [ userRoleValue ];
			return { userRole: userRoleArray };
		},
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
];

// ユーティリティ関数
export const generateId = () => {
	return 'vkdif_' + Math.random().toString(36).substr(2, 15) + '_' + Date.now().toString(36);
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
