import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuItem } from '@wordpress/components';
import { check, plus, moreVertical } from '@wordpress/icons';

const ControlsPanelHeader = ({ attributes, setAttributes }) => {
	const conditionItems = [
		{ key: 'conditionPageType', label: 'Page Type' },
		{ key: 'conditionPostType', label: 'Post Type' },
		{ key: 'conditionUserRole', label: 'User Role' },
		{ key: 'conditionCustomField', label: 'Custom Field' },
		{ key: 'conditionPeriodDisplay', label: 'Period Display' },
	];

	const defaultConditions = {
		conditionPageType: {
			properties: {
				ifPageType: "none",
			}
		},
		conditionPostType: {
			properties: {
				ifPostType: "none",
			}
		},
		conditionUserRole: {
			properties: {
				userRole: [],
			}
		},
		conditionCustomField: {
			properties: {
				customFieldName: "",
				customFieldRule: "valueExists",
				customFieldValue: "",
			}
		},
		conditionPeriodDisplay: {
			properties: {
				periodDisplaySetting: "none",
				periodSpecificationMethod: "direct",
				periodDisplayValue: "",
				periodReferCustomField: "",
			}
		},
	};

	// 条件の有効/無効を切り替える関数
	const toggleCondition = (key) => {
		// 既存の条件をディープコピー
		const newConditions = JSON.parse(JSON.stringify(attributes.conditions));

		// 指定されたキーに対応する条件の`enable`フラグをトグル
		newConditions[key].enable = !newConditions[key].enable;

		// もし条件が無効になった場合、プロパティをデフォルトにリセット
		if (!newConditions[key].enable) {
			newConditions[key].properties = defaultConditions[key].properties;
		}

		setAttributes({ conditions: newConditions });
	};

	// どれかの条件がenable: trueであるかどうかを確認する関数
	const isAnyConditionEnabled = () => {
		return conditionItems.some(item => attributes.conditions[item.key].enable);
	};

	return (
		<div className="controls-panel-header">
			<h2>Display Conditions</h2>
			<DropdownMenu
				icon={isAnyConditionEnabled() ? moreVertical : plus}
				label="Select Condition"
			>
				{({ onClose }) => (
					<>
						{conditionItems.map((item) => (
							<MenuItem
								key={item.key}
								onClick={() => {
									toggleCondition(item.key);
									onClose();
								}}
								icon={attributes.conditions[item.key].enable ? check : null}
							>
								{item.label}
							</MenuItem>
						))}
					</>
				)}
			</DropdownMenu>
		</div>
	);
};

export default ControlsPanelHeader;
