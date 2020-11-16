/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FormToggle } from "@library/forms/FormToggle";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";

interface IProps {
    variableKey: string;
    forcedValue?: boolean;
    afterChange?: (value: boolean) => void;
    disabled?: boolean;
}

export function ThemeToggle(props: IProps) {
    const { variableKey, forcedValue, afterChange, disabled } = props;
    const { generatedValue, setValue } = useThemeVariableField(variableKey);
    const { inputID, labelID } = useThemeBlock();

    const value = forcedValue ?? !!generatedValue;

    return (
        <FormToggle
            id={inputID}
            labelID={labelID}
            slim
            enabled={value}
            disabled={disabled}
            onChange={(newValue) => {
                setValue(newValue);
                afterChange?.(newValue);
            }}
        />
    );
}
