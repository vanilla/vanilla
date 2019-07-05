/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    borders,
    IBorderRadiusOutput,
    IBorderStyles,
    IBorderStylesWIP,
    BorderRadiusValue,
    IRadiusShorthand,
    radiusValue,
    IBorderStylesAll,
    IBorderRadiiDeclaration,
} from "@library/styles/styleHelpersBorders";
import merge from "lodash/merge";
import {capitalizeFirstLetter, logError} from "@vanilla/utils";
import {BorderRadiusProperty} from "csstype";
import {TLength} from "typestyle/lib/types";
import {border, borderStyle} from "csx";
import {unit} from "@library/styles/styleHelpers";
import {BorderOptions, BoxFunction} from "csx/lib/types";

/*
export const calculateBorders = (borderStyles: IBorderStyles | undefined | null, debug = false) => {

    if (debug) {
        window.console.log("");
        window.console.log("1 - 1: calculate border: ", borderStyles);
    }

    if (!!borderStyles) {
        const detailedResult: IBorderStylesWIP = {};
        const emptyBorders = {top: {}, right:{}, bottom:{}, left: {}};

        // Direct, global declaration for all
        // ----------------------------
        if (borderStyles.color !== undefined) {
            merge(detailedResult,emptyBorders);
            detailedResult.top!.color = borderStyles.color;
            detailedResult.right!.color = borderStyles.color;
            detailedResult.bottom!.color = borderStyles.color;
            detailedResult.left!.color = borderStyles.color;
        }
        if (borderStyles.width !== undefined) {
            merge(detailedResult, emptyBorders);
            detailedResult.top!.width = borderStyles.width;
            detailedResult.right!.width = borderStyles.width;
            detailedResult.bottom!.width = borderStyles.width;
            detailedResult.left!.width = borderStyles.width;
        }
        if (borderStyles.style !== undefined) {
            merge(detailedResult, emptyBorders);
            detailedResult.top!.style = borderStyles.style;
            detailedResult.right!.style = borderStyles.style;
            detailedResult.bottom!.style = borderStyles.style;
            detailedResult.left!.style = borderStyles.style;
        }


        // Detailed - detailedResult
        // ----------------------------
        // All (can be redundat if both global and all are set)

        if (borderStyles.all !== undefined) {
            // All styles declared as shorthand
            if (typeof borderStyles.all !== "string") { // shorthand
                const hasColor = borderStyles.all.color !== undefined;
                const hasWidth = borderStyles.all.width !== undefined;
                const hasStyle = borderStyles.all.style !== undefined;

                if (hasColor || hasWidth || hasStyle) {
                    if (detailedResult.top === undefined) {
                        detailedResult.top = {};
                    }
                    if (detailedResult.right === undefined) {
                        detailedResult.right = {};
                    }
                    if (detailedResult.bottom === undefined) {
                        detailedResult.bottom = {};
                    }
                    if (detailedResult.left === undefined) {
                        detailedResult.left = {};
                    }
                }

                if (borderStyles.all.color !== undefined) {
                    detailedResult.top!.color = borderStyles.all.color;
                    detailedResult.right!.color = borderStyles.all.color;
                    detailedResult.bottom!.color = borderStyles.all.color;
                    detailedResult.left!.color = borderStyles.all.color;

                }
                if (borderStyles.all.width !== undefined) {
                    detailedResult.top!.width = borderStyles.all.width;
                    detailedResult.right!.width = borderStyles.all.width;
                    detailedResult.bottom!.width = borderStyles.all.width;
                    detailedResult.left!.width = borderStyles.all.width;

                }
                if (borderStyles.all.style !== undefined) {
                    detailedResult.top!.style = borderStyles.all.style;
                    detailedResult.right!.style = borderStyles.all.style;
                    detailedResult.bottom!.style = borderStyles.all.style;
                    detailedResult.left!.style = borderStyles.all.style;

                }
            }
        }

        if(debug) {
            // window.console.log("2 ================ end of 'all': ", detailedResult);
        }

        if (borderStyles.topBottom !== undefined) {
            const hasColor = borderStyles.topBottom.color !== undefined;
            const hasWidth = borderStyles.topBottom.width !== undefined;
            const hasStyle = borderStyles.topBottom.style !== undefined;
            if (hasColor || hasWidth || hasStyle) {
                if (detailedResult.top === undefined) {
                    detailedResult.top = {};
                }
                if (detailedResult.bottom === undefined) {
                    detailedResult.bottom = {};
                }
            }
            if (hasColor) {
                detailedResult.top!.color = borderStyles.topBottom.color;
                detailedResult.bottom!.color = borderStyles.topBottom.color;

            }
            if (hasWidth) {
                detailedResult.top!.width = borderStyles.topBottom.width;
                detailedResult.bottom!.width = borderStyles.topBottom.width;

            }
            if (hasStyle) {
                detailedResult.top!.style = borderStyles.topBottom.style;
                detailedResult.bottom!.style = borderStyles.topBottom.style;

            }
        }

        if (borderStyles.leftRight !== undefined) {
            const hasColor = borderStyles.leftRight.color !== undefined;
            const hasWidth = borderStyles.leftRight.width !== undefined;
            const hasStyle = borderStyles.leftRight.style !== undefined;
            if (hasColor || hasWidth || hasStyle) {
                if (detailedResult.left === undefined) {
                    detailedResult.left = {};
                }
                if (detailedResult.right === undefined) {
                    detailedResult.right = {};
                }
            }
            if (borderStyles.leftRight.color !== undefined) {
                detailedResult.left!.color = borderStyles.leftRight.color;
                detailedResult.right!.color = borderStyles.leftRight.color;

            }
            if (borderStyles.leftRight.width !== undefined) {
                detailedResult.left!.width = borderStyles.leftRight.width;
                detailedResult.right!.width = borderStyles.leftRight.width;

            }
            if (borderStyles.leftRight.style !== undefined) {
                detailedResult.left!.style = borderStyles.leftRight.style;
                detailedResult.right!.style = borderStyles.leftRight.style;

            }
        }

        // Detailed - 1 Side
        // ----------------------------
        if (borderStyles.top !== undefined) {

            const hasColor = borderStyles.top.color !== undefined;
            const hasWidth = borderStyles.top.width !== undefined;
            const hasStyle = borderStyles.top.style !== undefined;

            if (hasColor || hasWidth || hasStyle) {
                if (detailedResult.top === undefined) {
                    detailedResult.top = {};
                }
            }

            if (borderStyles.top!.color !== undefined) {
                detailedResult.top!.color = borderStyles.top.color;

            }
            if (borderStyles.top!.width !== undefined) {
                detailedResult.top!.width = borderStyles.top.width;

            }
            if (borderStyles.top!.style !== undefined) {
                detailedResult.top!.style = borderStyles.top.style;

            }
        }

        if (borderStyles.right !== undefined) {

            const hasColor = borderStyles.right.color !== undefined;
            const hasWidth = borderStyles.right.width !== undefined;
            const hasStyle = borderStyles.right.style !== undefined;

            if (hasColor || hasWidth || hasStyle) {
                if (detailedResult.right === undefined) {
                    detailedResult.right = {};
                }
            }

            if (borderStyles.right.color !== undefined) {
                detailedResult.right!.color = borderStyles.right.color;

            }
            if (borderStyles.right.width !== undefined) {
                detailedResult.right!.width = borderStyles.right.width;

            }
            if (borderStyles.right.style !== undefined) {
                detailedResult.right!.style = borderStyles.right.style;

            }
        }


        if (borderStyles.bottom !== undefined) {

            const hasColor = borderStyles.bottom.color !== undefined;
            const hasWidth = borderStyles.bottom.width !== undefined;
            const hasStyle = borderStyles.bottom.style !== undefined;

            if (hasColor || hasWidth || hasStyle) {
                if (detailedResult.bottom === undefined) {
                    detailedResult.bottom = {};
                }
            }

            if (borderStyles.bottom.color !== undefined) {
                detailedResult.bottom!.color = borderStyles.bottom.color;

            }
            if (borderStyles.bottom.width !== undefined) {
                detailedResult.bottom!.width = borderStyles.bottom.width;

            }
            if (borderStyles.bottom.style !== undefined) {
                detailedResult.bottom!.style = borderStyles.bottom.style;

            }
        }

        if (borderStyles.left !== undefined) {
            const hasColor = borderStyles.left.color !== undefined;
            const hasWidth = borderStyles.left.width !== undefined;
            const hasStyle = borderStyles.left.style !== undefined;

            if (hasColor || hasWidth || hasStyle) {
                if (detailedResult.bottom === undefined) {
                    detailedResult.bottom = {};
                }
            }

            if (borderStyles.left.color !== undefined) {
                detailedResult.left!.color = borderStyles.left.color;

            }
            if (borderStyles.left.width !== undefined) {
                detailedResult.left!.width = borderStyles.left.width;

            }
            if (borderStyles.left.style !== undefined) {
                detailedResult.left!.style = borderStyles.left.style;

            }
        }

        if (borderStyles.radius !== undefined) {

            if (debug) {
                // window.console.log("before - borderRadiusCalculation: ", detailedResult);
            }

            const radius = borderRadiusCalculation(borderStyles.radius, debug);
            merge(detailedResult, radius);

            if (debug) {
                // window.console.log("after - borderRadiusCalculation: ", detailedResult);
            }
        }


        // if (debug) {
        //     window.console.log("globalResult == step == ", globalResult);
        //     window.console.log("detailedResult == step == ", detailedResult);
        // }



        if (debug) {
            window.console.log("");
            window.console.log("Final Output: ", detailedResult);
            window.console.log("");
        }
        return detailedResult;
    } else {
        return null;
    }
};

 */

export const setAllBorderRadii = (radius: BorderRadiusValue) => {
    return {
        topRight: radius,
        bottomRight: radius,
        bottomLeft: radius,
        topLeft: radius,
    };
};

const isStringOrNumber = (variable) => {
    const type = typeof variable;
    return !!type ? (type === "string" || type === "number") : false;
};

export const getValueIfItExists = (haystack: object, needle:string) => {
    if (checkIfKeyExistsAndIsDefined(haystack, needle)) {
        return haystack[needle];
    } else {
        return undefined;
    }
}

export const checkIfKeyExistsAndIsDefined = (haystack: object, needle:string) => {
    return needle in haystack && haystack[needle] !== undefined;
}
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
 */
export const borderRadiusCalculation = (borderRadiusStyles: IBorderRadiiDeclaration | radiusValue, debug = false) => {
    let output: IBorderRadiusOutput = {};
    if (debug) {
        window.console.log("=====> border radius IN:", borderRadiusStyles);
    }

    if (borderRadiusStyles !== undefined ) {
        if (isStringOrNumber(typeof borderRadiusStyles)) {
            //string or number -> same as "all"
            merge(output, setAllBorderRadii(borderRadiusStyles as BorderRadiusValue));

        } else {
            //all -> if has radius set all
            const allValue = getValueIfItExists(borderRadiusStyles as IBorderRadiusOutput, "all");
            if (allValue) {
                merge(output, setAllBorderRadii(allValue));
            }

            // top -> if has radius -> set topLeft and topRight radii
            if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiiDeclaration, "top")) {
                output.topRight = (borderRadiusStyles as IBorderRadiiDeclaration).top as BorderRadiusValue;
                output.topLeft = (borderRadiusStyles as IBorderRadiiDeclaration).top as BorderRadiusValue;
            }

            // bottom -> if has radius -> set bottomLeft and bottomRight radii
            if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiiDeclaration, "top")) {
                output.topRight = (borderRadiusStyles as IBorderRadiiDeclaration).top as BorderRadiusValue;
                output.topLeft = (borderRadiusStyles as IBorderRadiiDeclaration).top as BorderRadiusValue;
            }
            // left-> if has radius -> set topLeft and bottomLeft radii
            if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiiDeclaration, "left")) {
                output.topLeft = (borderRadiusStyles as IBorderRadiiDeclaration).left as BorderRadiusValue;
                output.bottomLeft = (borderRadiusStyles as IBorderRadiiDeclaration).left as BorderRadiusValue;
            }

            // right -> if has radius -> set topRight and bottomRight radii
            if (checkIfKeyExistsAndIsDefined(borderRadiusStyles as IBorderRadiiDeclaration, "right")) {
                output.topRight = (borderRadiusStyles as IBorderRadiiDeclaration).right as BorderRadiusValue;
                output.bottomRight = (borderRadiusStyles as IBorderRadiiDeclaration).right as BorderRadiusValue;
            }

            // Explicitly set corners
            if (checkIfKeyExistsAndIsDefined((borderRadiusStyles as IBorderRadiusOutput), "topRight")) {
                output.topRight = (borderRadiusStyles as IBorderRadiusOutput).topRight;
            }

            if (checkIfKeyExistsAndIsDefined((borderRadiusStyles as IBorderRadiusOutput), "bottomRight")) {
                output.bottomRight = (borderRadiusStyles as IBorderRadiusOutput).bottomRight;
            }

            if (checkIfKeyExistsAndIsDefined((borderRadiusStyles as IBorderRadiusOutput), "bottomLeft")) {
                output.bottomLeft = (borderRadiusStyles as IBorderRadiusOutput).bottomLeft;
            }
            if (checkIfKeyExistsAndIsDefined((borderRadiusStyles as IBorderRadiusOutput), "topLeft")) {
                output.topLeft = (borderRadiusStyles as IBorderRadiusOutput).topLeft;
            }
        }
    }
    return output;


    if (debug) {
        window.console.log("=====> border radius OUT: ", output);
    }
};



