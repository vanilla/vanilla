/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { important, px } from "csx";
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

/*
 * Helper to generate human readable classes generated from TypeStyle
 * @param componentName - The component's name.
 */
export const debugHelper = (componentName: string) => {
    return {
        name: (subElementName?: string) => {
            if (subElementName) {
                return { $debugName: `${componentName}-${subElementName}` };
            } else {
                return { $debugName: componentName };
            }
        },
    };
};

export const ifExistsWithFallback = checkProp => {
    if (checkProp && checkProp.length > 0) {
        const next = checkProp.pop();
        return next ? next : ifExistsWithFallback(checkProp);
    } else {
        return undefined;
    }
};

export const processValue = variable => {
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

export const unit = (
    val: string | number | undefined,
    options: { unitFunction?: (value) => string; isImportant?: boolean } = {} as any,
) => {
    if (val === undefined) {
        return undefined;
    }
    const { unitFunction = px, isImportant = false } = options;
    const valIsNumeric = val || val === 0 ? isNumeric(val.toString().trim()) : false;

    let output;

    if (typeof val === "string" && !valIsNumeric) {
        output = val;
    } else if (val !== undefined && val !== null && valIsNumeric) {
        output = unitFunction(val as number);
    } else {
        output = val;
    }

    if (isImportant) {
        return important(output);
    } else {
        return output;
    }
};

export const importantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = unit(val);
    return withUnit ? important(withUnit.toString()) : withUnit;
};

export const negativeImportantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = unit(val);
    return withUnit ? important(negative(withUnit).toString()) : withUnit;
};

export const negativeUnit = (val: string | number | undefined, unitFunction = px) => {
    return negative(unit(val));
};

export const negative = val => {
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
    return val !== undefined ? unit(val) : undefined;
};
