/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import CheckBox from "@library/forms/Checkbox";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";

interface IProps {
    variableKey: string;
    label: string;
}

export function ThemeBuilderCheckBox(props: IProps) {
    const { variableKey, label } = props;
    const classes = themeBuilderClasses();

    const { rawValue, generatedValue, setValue } = useThemeVariableField(variableKey);

    return (
        <div className={classes.block}>
            <span className={classes.label}></span>
            <span className={classes.checkBoxWrap}>
                <CheckBox
                    label={label}
                    isHorizontal
                    checked={!!generatedValue}
                    onChange={e => {
                        setValue(e.target.checked);
                    }}
                />
            </span>
        </div>
    );
}
