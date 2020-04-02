/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { ResetIcon } from "@library/icons/common";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import isEqual from "lodash/isEqual";

interface IProps extends Omit<React.ComponentProps<typeof Button>, "children" | "onClick"> {
    variableKey: string;
}

export function ThemeBuilderRevert(_props: IProps) {
    const { variableKey, ...props } = _props;
    const classes = themeBuilderClasses();
    const { initialValue, rawValue, setValue } = useThemeVariableField(variableKey);

    if (isEqual(initialValue, rawValue)) {
        return null;
    }

    return (
        <Button
            {...props}
            className={classes.resetButton}
            baseClass={ButtonTypes.ICON_COMPACT}
            title={t("Reset")}
            onClick={() => {
                setValue(initialValue);
            }}
        >
            <ResetIcon />
        </Button>
    );
}
