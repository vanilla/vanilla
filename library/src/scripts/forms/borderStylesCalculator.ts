/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBorderRadiusValue, IBorderRadiusOutput, radiusValue } from "@library/styles/styleHelpersBorders";
import merge from "lodash/merge";
import { unit } from "@library/styles/styleHelpers";

export const setAllBorderRadii = (radius: IBorderRadiusValue) => {
    return {
        topRight: radius,
        bottomRight: radius,
        bottomLeft: radius,
        topLeft: radius,
    };
};

// const isStringOrNumber = variable => {
//     const type = typeof variable;
//     return !!type ? type === "string" || type === "number" : false;
// };

export const getValueIfItExists = (
    haystack: object | undefined,
    needle: string,
    fallback: any = null,
    debug = false,
) => {
    if (!!haystack && checkIfKeyExistsAndIsDefined(haystack, needle)) {
        return haystack[needle];
    } else {
        if (typeof fallback !== null) {
            return fallback;
        } else {
            return undefined;
        }
    }
};

export const checkIfKeyExistsAndIsDefined = (haystack: object, needle: string) => {
    if (!!haystack && typeof haystack === "object" && !!needle) {
        return needle in haystack && haystack[needle] !== undefined;
    } else {
        return false;
    }
};
/*
    Can either be declared in the "radius" key explicitly, or as part of a side.
    direct string or number -> same as "all"
    all -> if has radius set all
    topBottom-> ignored, should use all
    leftRight-> ignored, should use all
    top -> if has radius -> set topLeft and topRight radii
    bottom -> if has radius -> set bottomLeft and bottomRight radii
    left-> if has radius -> set topLeft and bottomLeft radii
    right -> if has radius -> set topRight and bottomRight radii
    radius-> takes precedence if set, since it's more explicit.
    If string or number, take as is and set to all,
    If object: first check for shorthand (IRadiusShorthand),
    then for explicit sides (IBorderRadiusOutput)
//  */
// export const borderRadiusCalculation = (borderRadiusStyles: IBorderRadiusValue | radiusValue, debug = false) => {
//     const output: IBorderRadiusOutput = {};
//     if (debug) {
//         window.console.log("=====> border radius IN:", borderRadiusStyles);
//     }
//
//     if (borderRadiusStyles !== undefined) {
//         if (isStringOrNumber(borderRadiusStyles)) {
//             merge(output, setAllBorderRadii(borderRadiusStyles as IBorderRadiusValue));
//         } else {
//             //all -> if has radius set all
//             const allValue = getValueIfItExists(borderRadiusStyles as IBorderRadiusOutput, "all");
//             if (allValue) {
//                 merge(output, setAllBorderRadii(allValue));
//             }
//
//             // top -> if has radius -> set topLeft and topRight radii
//             const top = checkIfKeyExistsAndIsDefined(borderRadiusStyles as any, "top");
//             if (top) {
//                 output.topRightRadius = unit(top as any);
//                 output.topLeftRadius = unit(top as any);
//             }
//
//             // bottom -> if has radius -> set bottomLeft and bottomRight radii
//             const bottom = checkIfKeyExistsAndIsDefined(borderRadiusStyles as any, "bottom");
//             if (bottom) {
//                 output.bottomRightRadius = unit(bottom as any);
//                 output.bottomLeftRadius = unit(bottom as any);
//             }
//
//             // left-> if has radius -> set topLeft and bottomLeft radii
//             const left = checkIfKeyExistsAndIsDefined(borderRadiusStyles as any, "left");
//             if (left) {
//                 output.topLeftRadius = unit(left as any);
//                 output.bottomLeftRadius = unit(left as any);
//             }
//
//             // right -> if has radius -> set topRight and bottomRight radii
//             const right = checkIfKeyExistsAndIsDefined(borderRadiusStyles as any, "right");
//             if (right) {
//                 output.topRightRadius = unit(right as any);
//                 output.bottomRightRadius = unit(right as any);
//             }
//
//             // Explicitly set corners
//             if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiusOutput, "topRight")) {
//                 output.topRightRadius = (borderRadiusStyles as IBorderRadiusOutput).topRightRadius;
//             }
//
//             if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiusOutput, "bottomRight")) {
//                 output.bottomRightRadius = (borderRadiusStyles as IBorderRadiusOutput).bottomRightRadius;
//             }
//
//             if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiusOutput, "bottomLeft")) {
//                 output.bottomLeftRadius = (borderRadiusStyles as IBorderRadiusOutput).bottomLeftRadius;
//             }
//             if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiusOutput, "topLeft")) {
//                 output.topLeftRadius = (borderRadiusStyles as IBorderRadiusOutput).topLeftRadius;
//             }
//         }
//     }
//     return output;
//
//     if (debug) {
//         window.console.log("=====> border radius OUT: ", output);
//     }
// };
