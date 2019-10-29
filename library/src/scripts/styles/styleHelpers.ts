/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { important, px } from "csx";
export * from "@library/styles/styleHelpersAnimation";
export * from "@library/styles/styleHelpersBackgroundStyling";
export * from "@library/styles/styleHelpersTypography";
export * from "@library/styles/styleHelpersBorders";
export * from "@library/styles/styleHelpersButtons";
export * from "@library/styles/styleHelpersColors";
export * from "@library/styles/styleHelpersDropShadow";
export * from "@library/styles/styleHelpersFeedback";
export * from "@library/styles/styleHelpersfPadding";
export * from "@library/styles/styleHelpersLinks";
export * from "@library/styles/styleHelpersMargin";
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

export const unit = (val: string | number | undefined, unitFunction = px) => {
    if (typeof val === "string") {
        return val;
    } else if (val !== undefined && val !== null && !isNaN(val)) {
        return unitFunction(val as number);
    } else {
        return val;
    }
};

export const importantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = unit(val);
    return withUnit ? important(withUnit.toString()) : withUnit;
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
