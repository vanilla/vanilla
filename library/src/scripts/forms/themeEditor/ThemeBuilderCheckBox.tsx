/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import CheckBox from "@library/forms/Checkbox";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { ThemeInfoTooltip } from "@library/forms/themeEditor/ThemeInfoTooltip";
import classNames from "classnames";

interface IProps {
    variableKey: string;
    label: string;
    info?: React.ReactNode;
}

export function ThemeBuilderCheckBox(props: IProps) {
    const { variableKey, label } = props;
    const classes = themeBuilderClasses();

    const { rawValue, generatedValue, setValue } = useThemeVariableField(variableKey);

    return (
        <div className={classNames(classes.block, "checkBoxBlock")}>
            <span className={classes.label}></span>
            <span className={classes.checkBoxWrap}>
                <CheckBox
                    className={classes.checkBox}
                    label={label}
                    isHorizontal
                    checked={!!generatedValue}
                    onChange={(e) => {
                        setValue(e.target.checked);
                    }}
                />
                {props.info && <ThemeInfoTooltip label={props.info} />}
            </span>
        </div>
    );
}
