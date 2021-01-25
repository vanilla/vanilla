/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { ResetIcon } from "@library/icons/common";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import isEqual from "lodash/isEqual";
import { ColorHelper } from "csx";

interface IProps extends Omit<React.ComponentProps<typeof Button>, "children" | "onClick"> {
    variableKey: string;
    afterChange?: () => void;
}

function areColorsSame(colorA: any, colorB: any): boolean {
    const aHex = colorA instanceof ColorHelper ? colorA.toHexString() : colorA;
    const bHex = colorB instanceof ColorHelper ? colorB.toHexString() : colorB;

    return aHex === bHex;
}

export function ThemeBuilderRevert(_props: IProps) {
    const { variableKey, afterChange, ...props } = _props;
    const classes = themeBuilderClasses();
    const { initialValue, rawValue, generatedValue, setValue } = useThemeVariableField<any>(variableKey);

    if (
        initialValue == rawValue ||
        generatedValue == initialValue ||
        isEqual(initialValue, rawValue) ||
        isEqual(generatedValue, initialValue) ||
        areColorsSame(initialValue, rawValue) ||
        areColorsSame(generatedValue, initialValue)
    ) {
        return null;
    }

    return (
        <Button
            {...props}
            className={classes.resetButton}
            baseClass={ButtonTypes.ICON_COMPACT}
            title={t("Reset")}
            onClick={() => {
                setValue(initialValue ?? null); // Passing undefined doesn't clear a variable is initital wasn't set, we need to revert to null.
                afterChange && afterChange();
            }}
        >
            <ResetIcon />
        </Button>
    );
}
