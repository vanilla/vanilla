/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper, important, px } from "csx";
import isNumeric from "validator/lib/isNumeric";
export * from "@library/styles/styleHelpersAnimation";
export * from "@library/styles/styleHelpersBackgroundStyling";
export * from "@library/styles/styleHelpersTypography";
export * from "@library/styles/styleHelpersBorders";
export * from "@library/styles/styleHelpersButtons";
export * from "@library/styles/styleHelpersColors";
export * from "@library/styles/styleHelpersDropShadow";
export * from "@library/styles/styleHelpersFeedback";
export * from "@library/styles/styleHelpersSpacing";
export * from "@library/styles/styleHelpersLinks";
export * from "@library/styles/styleHelpersPositioning";
export * from "@library/styles/styleHelpersReset";
export * from "@library/styles/styleHelpersSpinner";
export * from "@library/styles/styleHelpersTypography";
export * from "@library/styles/styleHelpersVisibility";
import { styleUnit } from "@library/styles/styleUnit";
export const unit = styleUnit;

/*
 * Helper to generate human readable classes generated from TypeStyle
 * @param componentName - The component's name.
 */
export const debugHelper = (componentName: string) => {
    return {
        name: (subElementName?: string) => {
            if (subElementName) {
                return { label: `${componentName}-${subElementName}` };
            } else {
                return { label: componentName };
            }
        },
    };
};

export const ifExistsWithFallback = (checkProp) => {
    if (checkProp && checkProp.length > 0) {
        const next = checkProp.pop();
        return next ? next : ifExistsWithFallback(checkProp);
    } else {
        return undefined;
    }
};

export const processValue = (variable) => {
    const importantString = " !important";
    const isImportant: boolean = typeof variable === "string" && variable.endsWith(importantString);
    let value = variable;

    if (isImportant) {
        if (isNumeric(value as string)) {
            value = Number(value);
        }
    }

    return {
        value,
        isImportant,
    };
};

export const importantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = styleUnit(val);
    return withUnit ? important(withUnit.toString()) : withUnit;
};

export const negativeImportantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = styleUnit(val);
    return withUnit ? important(negative(withUnit).toString()) : withUnit;
};

export const negativeUnit = (val: string | number | undefined, unitFunction = px) => {
    return negative(styleUnit(val));
};

export const negative = (val) => {
    if (typeof val === "string") {
        val = val.trim();
        if (val.startsWith("-")) {
            return val.substring(1, val.length).trim();
        } else {
            return `-${val}`;
        }
    } else if (!!val && !isNaN(val)) {
        return val * -1;
    } else {
        return val;
    }
};

export const unitIfDefined = (val: string | number | undefined, unitFunction = px) => {
    return val !== undefined ? styleUnit(val) : undefined;
};

export interface IStateColors {
    allStates?: ColorHelper; // Applies to all
    noState?: ColorHelper; // Applies to stateless link
    hover?: ColorHelper;
    focus?: ColorHelper;
    clickFocus?: ColorHelper; // Focused, not through keyboard
    keyboardFocus?: ColorHelper; // Optionally different state for keyboard accessed element. Will default to "focus" state if not set.
    active?: ColorHelper;
    source?: string; // for debugging
}
