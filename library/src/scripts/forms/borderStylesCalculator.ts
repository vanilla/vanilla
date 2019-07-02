/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    borders,
    IBorderRadiusOutput,
    IBorderStyles, IBorderStylesWIP, BorderRadiusValue, IRadiusDeclaration, radiusValue, IBorderStylesAll,
} from "@library/styles/styleHelpersBorders";
import merge from "lodash/merge";
import {capitalizeFirstLetter} from "@vanilla/utils";
import {BorderRadiusProperty} from "csstype";
import {TLength} from "typestyle/lib/types";
import {border} from "csx";
import {unit} from "@library/styles/styleHelpers";


export const calculateBorders = (borderStyles: IBorderStyles | undefined | null, debug = false) => {

    if (debug) {
        window.console.log("raw data: ", borderStyles);
    }

    if (!!borderStyles) {
        let output;
        let hasGlobalStyles = false;
        let hasDetailedStyles = false;
        const globalResult: any = {};
        const detailedResult: IBorderStylesWIP = {};

        //
        // if (debug) {
        //     window.console.log("calculateBorders == in == ", borderStyles);
        // }

        // Global - globalResult
        // ----------------------------
        if (borderStyles.color !== undefined) {
            globalResult.color = borderStyles.color;
            hasGlobalStyles = true;
        }
        if (borderStyles.width !== undefined) {
            globalResult.width = borderStyles.width;
            hasGlobalStyles = true;
        }
        if (borderStyles.style !== undefined) {
            globalResult.style = borderStyles.style;
            hasGlobalStyles = true;
        }


        // Detailed - detailedResult
        // ----------------------------
        // All (can be redundat if both global and all are set)

        if (borderStyles.all !== undefined) {

            if (typeof borderStyles.all === "string") { // shorthand
                const styles = border(borderStyles.all);
                const breako = "here";
            } else {
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
                    hasDetailedStyles = true;
                }
                if (borderStyles.all.width !== undefined) {
                    detailedResult.top!.width = borderStyles.all.width;
                    detailedResult.right!.width = borderStyles.all.width;
                    detailedResult.bottom!.width = borderStyles.all.width;
                    detailedResult.left!.width = borderStyles.all.width;
                    hasDetailedStyles = true;
                }
                if (borderStyles.all.style !== undefined) {
                    detailedResult.top!.style = borderStyles.all.style;
                    detailedResult.right!.style = borderStyles.all.style;
                    detailedResult.bottom!.style = borderStyles.all.style;
                    detailedResult.left!.style = borderStyles.all.style;
                    hasDetailedStyles = true;
                }
            }
        }

        // if(debug) {
        //     window.console.log("2 ================ spot check: ", detailedResult);
        // }

        // Detailed - 2 Sides
        // ----------------------------
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
                hasDetailedStyles = true;
            }
            if (hasWidth) {
                detailedResult.top!.width = borderStyles.topBottom.width;
                detailedResult.bottom!.width = borderStyles.topBottom.width;
                hasDetailedStyles = true;
            }
            if (hasStyle) {
                detailedResult.top!.style = borderStyles.topBottom.style;
                detailedResult.bottom!.style = borderStyles.topBottom.style;
                hasDetailedStyles = true;
            }
        }

        // if(debug) {
        //     window.console.log("================ spot check: ", detailedResult);
        // }

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
                hasDetailedStyles = true;
            }
            if (borderStyles.leftRight.width !== undefined) {
                detailedResult.left!.width = borderStyles.leftRight.width;
                detailedResult.right!.width = borderStyles.leftRight.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.leftRight.style !== undefined) {
                detailedResult.left!.style = borderStyles.leftRight.style;
                detailedResult.right!.style = borderStyles.leftRight.style;
                hasDetailedStyles = true;
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
                hasDetailedStyles = true;
            }
            if (borderStyles.top!.width !== undefined) {
                detailedResult.top!.width = borderStyles.top.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.top!.style !== undefined) {
                detailedResult.top!.style = borderStyles.top.style;
                hasDetailedStyles = true;
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
                hasDetailedStyles = true;
            }
            if (borderStyles.right.width !== undefined) {
                detailedResult.right!.width = borderStyles.right.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.style !== undefined) {
                detailedResult.right!.style = borderStyles.right.style;
                hasDetailedStyles = true;
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
                hasDetailedStyles = true;
            }
            if (borderStyles.bottom.width !== undefined) {
                detailedResult.bottom!.width = borderStyles.bottom.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.bottom.style !== undefined) {
                detailedResult.bottom!.style = borderStyles.bottom.style;
                hasDetailedStyles = true;
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
                hasDetailedStyles = true;
            }
            if (borderStyles.left.width !== undefined) {
                detailedResult.left!.width = borderStyles.left.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.left.style !== undefined) {
                detailedResult.left!.style = borderStyles.left.style;
                hasDetailedStyles = true;
            }
        }

        if (borderStyles.radius !== undefined) {
            window.console.log("before: ", detailedResult);
            const radius = borderRadiusCalculation(borderStyles.radius);
            merge(detailedResult, radius);
            window.console.log("after: ", detailedResult);
        }


        if (debug) {
            window.console.log("globalResult == step == ", globalResult);
            window.console.log("detailedResult == step == ", detailedResult);
        }

        if (hasGlobalStyles && !hasDetailedStyles) {
            output = globalResult;
        } else if (!hasGlobalStyles && hasDetailedStyles) {
            output = detailedResult;
        } else {
            output = merge(globalResult, detailedResult);
        }

        if (debug) {
            window.console.log("output: ", output);
        }
        return output;
    } else {
        return null;
    }
};

// export enum radiusLocations {
//     BOTTOM_RIGHT = 'bottomRight',
//     BOTTOMLEFT = "bottomLeft",
//     TOP_RIGHT= "topRight",
//     TOP_LEFT= "topLeft",
// }
//
// export enum horizontalKeys {
//     LEFT = 'left',
//     RIGHT = 'right',
// }
// export enum verticalKeys {
//     TOP = 'top',
//     BOTTOM = 'bottom',
// }


const setAllBorderRadii = (radius: BorderRadiusValue) => {
    return {
        borderTopLeftRadius: radius,
        borderTopRightRadius: radius,
        borderBottomLeftRadius: radius,
        borderBottomRightRadius: radius,
    };
};


export const borderRadiusCalculation = (borderRadiusStyles: IBorderStyles | radiusValue | IRadiusDeclaration) => {
    let output: IBorderRadiusOutput = {};
    window.console.log("borderRadiusStyles", borderRadiusStyles);

    if (borderRadiusStyles !== undefined ) {
        if (typeof borderRadiusStyles === "string" || typeof borderRadiusStyles === "number") {
            merge(output, setAllBorderRadii(borderRadiusStyles as radiusValue));
        } else {
            if (borderRadiusStyles.all !== undefined) {
                // const allStyles:BorderRadiusValue = borderRadiusStyles.all;
                if (borderRadiusStyles.all === "string" || borderRadiusStyles.all === "number") {
                    merge(output, setAllBorderRadii(borderRadiusStyles as radiusValue));
                } else {
                    if (borderRadiusStyles.all !== undefined) {
                        merge(output, setAllBorderRadii(borderRadiusStyles.all as BorderRadiusValue));
                    }

                    // Top
                    if (borderRadiusStyles.top !== undefined && borderRadiusStyles.top.radius !== undefined) {
                        if (typeof borderRadiusStyles.top.radius === "object") {
                            output.borderTopLeftRadius = borderRadiusStyles.top.radius.left;
                            output.borderTopRightRadius = borderRadiusStyles.top.radius.right;
                        } else {
                            output.borderTopLeftRadius = borderRadiusStyles.top.radius;
                            output.borderTopRightRadius = borderRadiusStyles.top.radius;
                        }
                    }

                    // Right
                    if (borderRadiusStyles.right !== undefined && borderRadiusStyles.right.radius !== undefined) {
                        if (typeof borderRadiusStyles.right.radius === "object") {
                            output.borderTopRightRadius = borderRadiusStyles.right.radius.top;
                            output.borderBottomRightRadius = borderRadiusStyles.right.radius.bottom;
                        } else {
                            output.borderTopRightRadius = borderRadiusStyles.right.radius;
                            output.borderBottomRightRadius = borderRadiusStyles.right.radius;
                        }
                    }

                    // Bottom
                    if (borderRadiusStyles.bottom !== undefined && borderRadiusStyles.bottom.radius !== undefined) {
                        if (typeof borderRadiusStyles.bottom.radius === "object") {
                            output.borderBottomLeftRadius = borderRadiusStyles.bottom.radius.left;
                            output.borderBottomRightRadius = borderRadiusStyles.bottom.radius.right;
                        } else {
                            output.borderBottomLeftRadius = borderRadiusStyles.bottom.radius;
                            output.borderBottomRightRadius = borderRadiusStyles.bottom.radius;
                        }
                    }

                    // Left
                    if (borderRadiusStyles.left !== undefined && borderRadiusStyles.left.radius !== undefined) {
                        if (typeof borderRadiusStyles.left.radius === "object") {
                            output.borderTopLeftRadius = borderRadiusStyles.left.radius.top;
                            output.borderBottomLeftRadius = borderRadiusStyles.left.radius.bottom;
                        } else {
                            output.borderTopLeftRadius = borderRadiusStyles.left.radius;
                            output.borderBottomLeftRadius = borderRadiusStyles.left.radius;
                        }
                    }

                    // == Specified in "radius"
                    if (borderRadiusStyles.radius !== undefined) {
                        const radiusType = typeof borderRadiusStyles.radius;
                        if ( radiusType === "string" || radiusType === "number") {
                            merge(output, setAllBorderRadii(borderRadiusStyles.radius as BorderRadiusValue));
                        } else if (radiusType === "object") {
                            merge(output, borderRadiusCalculation(borderRadiusStyles.radius as any));
                        }
                    }
                }
            }


        }

    }


    return output;
};
