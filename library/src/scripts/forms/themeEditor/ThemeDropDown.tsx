/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import ErrorMessages from "@library/forms/ErrorMessages";
import SelectOne, { IMenuPlacement, MenuPlacement } from "@library/forms/select/SelectOne";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React from "react";
import { themeDropDownClasses } from "@library/forms/themeEditor/ThemeDropDown.styles";

interface IProps extends IMenuPlacement {
    variableKey: string; // If it exists, it will behave like a regular input. If not, the value(s) need to be handled manually with hidden input type.
    options: IComboBoxOption[];
    disabled?: boolean;
    afterChange?: (value: string | null | undefined) => void;
}

export function ThemeDropDown(_props: IProps) {
    const { options, variableKey, disabled, afterChange } = _props;
    const { inputID, labelID } = useThemeBlock();
    const { generatedValue, rawValue, setValue } = useThemeVariableField(variableKey);

    const onChange = (option: IComboBoxOption | undefined) => {
        const newValue = option ? option.value.toString() : undefined;
        setValue(newValue);
        afterChange?.(newValue);
    };

    const selectedOption = options.find(option => {
        if (option.value === rawValue) {
            return true;
        }
    });

    const defaultOption = options.find(option => {
        if (option.value === generatedValue) {
            return true;
        }
    }) ?? {
        label: t("Unknown"),
        value: generatedValue,
    };

    return (
        <div className={themeDropDownClasses().root}>
            <SelectOne
                label={null}
                labelID={labelID}
                inputID={inputID}
                options={options}
                value={selectedOption}
                placeholder={defaultOption.label}
                disabled={disabled ?? options.length === 1}
                menuPlacement={MenuPlacement.AUTO}
                onChange={onChange}
            />
        </div>
    );
}
