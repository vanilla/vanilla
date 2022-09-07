/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorHelper, px } from "csx";
import { isNumeric } from "@vanilla/utils";
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
export * from "@library/styles/styleUnit";

import { styleUnit } from "@library/styles/styleUnit";
import { internalAbsoluteMixins } from "@library/styles/MixinsAbsolute";
export const unit = styleUnit;

/** @deprecated Use Mixins.absolute instead */
export const absolutePosition = internalAbsoluteMixins;

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
