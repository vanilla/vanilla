/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import { t } from "@vanilla/i18n";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import CheckBox from "@library/forms/Checkbox";

interface ISelectCommentStatusProps {
    value: boolean | undefined;
    onChange: (newValue: boolean | undefined) => void;
    label?: string;
}

export default function SelectCommentStatus(props: ISelectCommentStatusProps) {
    const { value, onChange, label } = props;

    function handleChange(checkboxName) {
        // undefined indicates both are checked, so either can be unchecked
        if (value === undefined) {
            if (checkboxName === "hasNoCommentsCheckbox") {
                // 'Has no comments' is being unchecked, so hasComments = true
                onChange(true);
            } else {
                // 'Has comments' is being unchecked, so hasComments = false
                onChange(false);
            }
        }
        // 'Has comments' is checked, 'Has no comments' is unchecked
        else if (value === true) {
            if (checkboxName === "hasNoCommentsCheckbox") {
                // 'Has no comments' is being checked, so now both should be checked
                onChange(undefined);
            }
        }
        // 'Has no comments' us checked, 'Has comments' is unchecked
        else {
            if (checkboxName === "hasCommentsCheckbox") {
                // 'Has comments' is being checked, so now both should be checked
                onChange(undefined);
            }
        }
    }

    return (
        <CheckboxGroup legend={label}>
            <CheckBox
                key={"hasNoCommentsCheckbox"}
                label={t("No Comments")}
                labelBold={false}
                checked={value === false || value === undefined}
                onChange={() => handleChange("hasNoCommentsCheckbox")}
            />

            <CheckBox
                key={"hasCommentsCheckbox"}
                label={t("Has Comments")}
                labelBold={false}
                checked={value === true || value === undefined}
                onChange={() => handleChange("hasCommentsCheckbox")}
            />
        </CheckboxGroup>
    );
}
