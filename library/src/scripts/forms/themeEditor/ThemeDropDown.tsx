/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { IMenuPlacement, MenuPlacement } from "@library/forms/select/SelectOne";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { t } from "@vanilla/i18n";
import React, { useEffect } from "react";
import { themeDropDownClasses } from "@library/forms/themeEditor/ThemeDropDown.styles";
import { ThemeBuilderRevert } from "@library/forms/themeEditor/ThemeBuilderRevert";

interface IProps extends IMenuPlacement {
    variableKey: string; // If it exists, it will behave like a regular input. If not, the value(s) need to be handled manually with hidden input type.
    options: IComboBoxOption[];
    disabled?: boolean;
    afterChange?: (value: string | null | undefined) => void;
}

export function ThemeDropDown(_props: IProps) {
    const { options, variableKey, disabled, afterChange } = _props;
    const { inputID, labelID } = useThemeBlock();
    const { generatedValue, initialValue, rawValue, setValue } = useThemeVariableField(variableKey);

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

    useEffect(() => {
        if (afterChange) {
            console.log("trigger use effect Theme Dropdown");
            afterChange(defaultOption.value);
        }
    }, []);

    return (
        <>
            <div className={themeDropDownClasses().root}>
                <SelectOne
                    key={rawValue}
                    label={null}
                    labelID={labelID}
                    inputID={inputID}
                    options={options}
                    value={selectedOption}
                    placeholder={defaultOption.label}
                    defaultValue={defaultOption}
                    disabled={disabled ?? options.length === 1}
                    menuPlacement={MenuPlacement.AUTO}
                    isClearable={false}
                    onChange={onChange}
                />
            </div>
            <ThemeBuilderRevert
                variableKey={variableKey}
                afterChange={
                    afterChange
                        ? () => {
                              afterChange(
                                  selectedOption && selectedOption.value ? selectedOption.value.toString() : undefined,
                              );
                          }
                        : undefined
                }
            />
        </>
    );
}
