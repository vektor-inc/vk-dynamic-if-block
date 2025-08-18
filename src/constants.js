import { __ } from '@wordpress/i18n';

// 条件タイプの定義
export const CONDITION_TYPE_LABELS = {
	pageType: __( 'Page Type', 'vk-dynamic-if-block' ),
	postType: __( 'Post Type', 'vk-dynamic-if-block' ),
	taxonomy: __( 'Taxonomy', 'vk-dynamic-if-block' ),
	language: __( 'Language', 'vk-dynamic-if-block' ),
	userRole: __( 'User Role', 'vk-dynamic-if-block' ),
	postAuthor: __( 'Post Author', 'vk-dynamic-if-block' ),
	customField: __( 'Custom Field', 'vk-dynamic-if-block' ),
	period: __( 'Display Period', 'vk-dynamic-if-block' ),
	loginUser: __( 'Login User Only', 'vk-dynamic-if-block' ),
	mobileDevice: __( 'Mobile Device Only', 'vk-dynamic-if-block' ),
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
		condition: ( val ) => val,
	},
];

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
