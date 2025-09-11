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
import { NestedSelect } from "@library/forms/nestedSelect";

interface IProps extends IMenuPlacement {
    variableKey: string; // If it exists, it will behave like a regular input. If not, the value(s) need to be handled manually with hidden input type.
    options: IComboBoxOption[];
    disabled?: boolean;
    afterChange?: (value: string | null | undefined) => void;
    forceDefaultKey?: string;
}

export function ThemeDropDown(_props: IProps) {
    const { options, variableKey, disabled, afterChange, forceDefaultKey } = _props;
    const { inputID, labelID } = useThemeBlock();
    const { generatedValue, initialValue, rawValue, setValue } = useThemeVariableField<string>(variableKey);

    const onChange = (option: IComboBoxOption | undefined) => {
        const newValue = option ? option.value.toString() : undefined;
        setValue(newValue);
        afterChange?.(newValue);
    };

    const selectedOption = options.find((option) => {
        if (option.value === rawValue) {
            return true;
        }
    });

    const defaultOption =
        options.find((option) => {
            if (forceDefaultKey) {
                return option.value === forceDefaultKey;
            } else {
                return option.value === generatedValue;
            }
        }) ??
        ({
            label: t("Unknown"),
            value: generatedValue,
        } as IComboBoxOption);

    useEffect(() => {
        if (afterChange) {
            afterChange(String(defaultOption.value));
        }
    }, []);

    return (
        <>
            <div className={themeDropDownClasses().root}>
                <NestedSelect
                    compact={true}
                    options={options}
                    value={rawValue ?? defaultOption.value}
                    classes={{
                        input: themeDropDownClasses().select,
                    }}
                    onChange={(newOption) => {
                        setValue(newOption as string);
                    }}
                    disabled={disabled}
                    isClearable={false}
                    labelID={labelID}
                    inputID={inputID}
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
