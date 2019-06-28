/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    borderStylesBySideBottom,
    borderStylesBySideLeft,
    borderStylesBySideRight, borderStylesBySideTop,
    IBorderRadii,
    IBorderStyles,
    IBottomBorderRadii, ILeftBorderRadii,
    IRightBorderRadii,
    ITopBorderRadii, radiusValue
} from "@library/styles/styleHelpersBorders";
import merge from "lodash/merge";


export const calculateBorders = (borderStyles: IBorderStyles | undefined | null, debug = false) => {

    if (debug) {
        window.console.log("raw data: ", borderStyles);
    }

    if (!!borderStyles) {
        let output;
        let hasGlobalStyles = false;
        let hasDetailedStyles = false;
        const globalResult: any = {};
        const detailedResult: any = {
            top: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            right: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            bottom: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            left: {
                color: undefined,
                width: undefined,
                style: undefined,
                radius: undefined,
            },
            radius: {
                topRight: undefined,
                bottomRight: undefined,
                topLeft: undefined,
                bottomLeft: undefined,
            },
        };

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
            const borderStylesAll = borderStyles.all;
            if (borderStylesAll.color) {
                detailedResult.top.color = borderStylesAll.color;
                detailedResult.right.color = borderStylesAll.color;
                detailedResult.bottom.color = borderStylesAll.color;
                detailedResult.left.color = borderStylesAll.color;
                hasDetailedStyles = true;
            }
            if (borderStylesAll.width) {
                detailedResult.top.width = borderStylesAll.width;
                detailedResult.right.width = borderStylesAll.width;
                detailedResult.bottom.width = borderStylesAll.width;
                detailedResult.left.width = borderStylesAll.width;
                hasDetailedStyles = true;
            }
            if (borderStylesAll.style) {
                detailedResult.top.style = borderStylesAll.style;
                detailedResult.right.style = borderStylesAll.style;
                detailedResult.bottom.style = borderStylesAll.style;
                detailedResult.left.style = borderStylesAll.style;
                hasDetailedStyles = true;
            }
        }

        if(debug) {
            window.console.log("2 ================ spot check: ", detailedResult);
        }

        // Detailed - 2 Sides
        // ----------------------------
        if (borderStyles.topBottom !== undefined) {
            const stylesTopBottom = borderStyles.topBottom;
            if (stylesTopBottom.color !== undefined) {
                detailedResult.top.color = stylesTopBottom.color;
                detailedResult.bottom.color = stylesTopBottom.color;
                hasDetailedStyles = true;
            }
            if (stylesTopBottom.width !== undefined) {
                detailedResult.top.width = stylesTopBottom.width;
                detailedResult.bottom.width = stylesTopBottom.width;
                hasDetailedStyles = true;
            }
            if (stylesTopBottom.style !== undefined) {
                detailedResult.top.style = stylesTopBottom.style;
                detailedResult.bottom.style = stylesTopBottom.style;
                hasDetailedStyles = true;
            }
        }

        // if(debug) {
        //     window.console.log("================ spot check: ", detailedResult);
        // }

        if (borderStyles.leftRight !== undefined) {
            const stylesLeftRight = borderStyles.leftRight;
            if (stylesLeftRight.color !== undefined) {
                detailedResult.left.color = stylesLeftRight.color;
                detailedResult.right.color = stylesLeftRight.color;
                hasDetailedStyles = true;
            }
            if (stylesLeftRight.width !== undefined) {
                detailedResult.left.width = stylesLeftRight.width;
                detailedResult.right.width = stylesLeftRight.width;
                hasDetailedStyles = true;
            }
            if (stylesLeftRight.style !== undefined) {
                detailedResult.left.style = stylesLeftRight.style;
                detailedResult.right.style = stylesLeftRight.style;
                hasDetailedStyles = true;
            }
        }

        // Detailed - 1 Side
        // ----------------------------
        if (borderStyles.top !== undefined) {
            if (borderStyles.top.color !== undefined) {
                detailedResult.top.color = borderStyles.top.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.top.width !== undefined) {
                detailedResult.top.width = borderStyles.top.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.top.style !== undefined) {
                detailedResult.top.style = borderStyles.top.style;
                hasDetailedStyles = true;
            }
            if (borderStyles.top.radius !== undefined) {
                const topRadius = borderStyles.top.radius as ITopBorderRadii;
                if (typeof topRadius === "object") {
                    detailedResult.radius.topRight = topRadius.right ? topRadius.right : undefined;
                    detailedResult.radius.topLeft = topRadius.left ? topRadius.left : undefined;
                } else {
                    detailedResult.radius.topRight = topRadius;
                    detailedResult.radius.topLeft = topRadius;
                }
                hasDetailedStyles = true;
            }
        }

        if (borderStyles.right !== undefined) {
            if (borderStyles.right.color !== undefined) {
                detailedResult.right.color = borderStyles.right.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.width !== undefined) {
                detailedResult.right.width = borderStyles.right.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.style !== undefined) {
                detailedResult.right.style = borderStyles.right.style;
                hasDetailedStyles = true;
            }
            if (borderStyles.right.radius !== undefined) {
                const rightBorderRadius = borderStyles.right.radius as IRightBorderRadii;
                if (typeof rightBorderRadius === "object") {
                    detailedResult.radius.topRight = rightBorderRadius.top ? rightBorderRadius.top : undefined;
                    detailedResult.radius.bottomtLeft = rightBorderRadius.bottom ? rightBorderRadius.bottom : undefined;
                } else {
                    detailedResult.radius.rightRight = rightBorderRadius;
                    detailedResult.radius.rightLeft = rightBorderRadius;
                }
                hasDetailedStyles = true;
            }
        }


        if (borderStyles.bottom !== undefined) {
            if (borderStyles.bottom.color !== undefined) {
                detailedResult.bottom.color = borderStyles.bottom.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.bottom.width !== undefined) {
                detailedResult.bottom.width = borderStyles.bottom.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.bottom.style !== undefined) {
                detailedResult.bottom.style = borderStyles.bottom.style;
                hasDetailedStyles = true;
            }


            if (borderStyles.bottom.radius !== undefined) {
                const bottomBorderRadius = borderStyles.bottom.radius as IBottomBorderRadii;
                if (typeof bottomBorderRadius === "object") {
                    detailedResult.radius.bottomRight = bottomBorderRadius.right ? bottomBorderRadius.right : undefined;
                    detailedResult.radius.bottomtLeft = bottomBorderRadius.left ? bottomBorderRadius.left : undefined;
                } else {
                    detailedResult.radius.bottomRight = bottomBorderRadius;
                    detailedResult.radius.bottomtLeft = bottomBorderRadius;
                }
                hasDetailedStyles = true;
            }
        }

        if (borderStyles.left !== undefined) {
            if (borderStyles.left.color !== undefined) {
                detailedResult.left.color = borderStyles.left.color;
                hasDetailedStyles = true;
            }
            if (borderStyles.left.width !== undefined) {
                detailedResult.left.width = borderStyles.left.width;
                hasDetailedStyles = true;
            }
            if (borderStyles.left.style !== undefined) {
                detailedResult.left.style = borderStyles.left.style;
                hasDetailedStyles = true;
            }

            const calculateBorderRadii = verticalOrHorizontalBorderRadiusCalculation(borderStyles.left, true);
            console.log("calculateBorderRadii: ", calculateBorderRadii);
            // detailedResult.radius.topLeft = calculateBorderRadii[horizontalKeys.LEFT];
            // detailedResult.radius.rightLeft = calculateBorderRadii[horizontalKeys.RIGHT];

        }

        if (borderStyles.radius !== undefined) {
            const radius = borderStyles.radius;
            if (typeof radius === "object") {
                if (radius.all !== undefined) {
                    detailedResult.radius.topRight = radius.all;
                    detailedResult.radius.bottomRight = radius.all;
                    detailedResult.radius.bottomLeft = radius.all;
                    detailedResult.radius.topLeft = radius.all;
                    hasDetailedStyles = true;
                }

                if (radius.top !== undefined) {
                    detailedResult.radius.topRight = radius.top;
                    detailedResult.radius.topLeft = radius.top;
                    hasDetailedStyles = true;
                }

                if (radius.bottom !== undefined) {
                    detailedResult.radius.bottomRight = radius.bottom;
                    detailedResult.radius.bottomLeft = radius.bottom;
                    hasDetailedStyles = true;
                }

                if (radius.left !== undefined) {
                    detailedResult.radius.topLeft = radius.left;
                    detailedResult.radius.bottomLeft = radius.left;
                    hasDetailedStyles = true;
                }

                if (radius.right !== undefined) {
                    detailedResult.radius.topRight = radius.right;
                    detailedResult.radius.bottomRight = radius.right;
                    hasDetailedStyles = true;
                }

                if (radius.topRight !== undefined) {
                    detailedResult.radius.topRight = radius.topRight;
                    hasDetailedStyles = true;
                }

                if (radius.topLeft !== undefined) {
                    detailedResult.radius.topLeft = radius.topLeft;
                    hasDetailedStyles = true;
                }

                if (radius.bottomRight !== undefined) {
                    detailedResult.radius.bottomRight = radius.bottomRight;
                    hasDetailedStyles = true;
                }

                if (radius.bottomLeft !== undefined) {
                    detailedResult.radius.bottomLeft = radius.bottomLeft;
                    hasDetailedStyles = true;
                }
            } else {
                detailedResult.radius = {
                    topRight: radius,
                    bottomRight: radius,
                    topLeft: radius,
                    bottomLeft: radius,
                };
            }
        }



        // Clean up undefined
        cleanUpBorderValues(globalResult);
        cleanUpBorderValues(detailedResult);

        // if (debug) {
        //     window.console.log("globalResult == step == ", globalResult);
        //     window.console.log("detailedResult == step == ", detailedResult);
        // }

        if (hasGlobalStyles && !hasDetailedStyles) {
            output = globalResult;
        } else if (!hasGlobalStyles && hasDetailedStyles) {
            output = detailedResult;
        } else {
            output = merge(globalResult, detailedResult);
        }


        // if (debug) {
        //     window.console.log("output: ", output);
        // }
        return output;
    } else {
        return null;
    }
};

const cleanUpBorderValues = (values) => {
    if (values) {
        if (values.radius){
            if (values.radius.topLeft === undefined) {
                delete values.radius.topLeft;
            }
            if (values.radius.topRight === undefined) {
                delete values.radius.topRight;
            }
            if (values.radius.bottomLeft === undefined) {
                delete values.radius.bottomLeft;
            }
            if (values.radius.bottomRight === undefined) {
                delete values.radius.bottomRight;
            }
        }
        if (values.top) {
            if (values.top && values.top.color === undefined) {
                delete values.top.color;
            }
            if (values.top.width === undefined) {
                delete values.top.width;
            }
            if (values.top.style === undefined) {
                delete values.top.style;
            }
        }
        if (values.right) {
            if (values.right.color === undefined) {
                delete values.right.color;
            }
            if (values.right.width === undefined) {
                delete values.right.width;
            }
            if (values.right.style === undefined) {
                delete values.right.style;
            }
        }
        if (values.bottom) {
            if (values.bottom.color === undefined) {
                delete values.bottom.color;
            }
            if (values.bottom.width === undefined) {
                delete values.bottom.width;
            }
            if (values.bottom.style === undefined) {
                delete values.bottom.style;
            }
        }
        if (values.left) {
            if (values.left.color === undefined) {
                delete values.left.color;
            }
            if (values.left.width === undefined) {
                delete values.left.width;
            }
            if (values.left.style === undefined) {
                delete values.left.style;
            }
        }
    }
};


export enum radiusLocations {
    BOTTOM_RIGHT = 'bottomRight',
    BOTTOMLEFT = "bottomLeft",
    TOP_RIGHT= "topRight",
    TOP_LEFT= "topLeft",
}

export enum horizontalKeys {
    LEFT = 'left',
    RIGHT = 'right',
}
export enum verticalKeys {
    TOP = 'top',
    BOTTOM = 'bottom',
}

type borderRadiiTypes = IBottomBorderRadii | ILeftBorderRadii | ITopBorderRadii | IRightBorderRadii;


export const verticalOrHorizontalBorderRadiusCalculation = (radiusStyles: undefined | borderStylesBySideRight | borderStylesBySideLeft | borderStylesBySideTop | borderStylesBySideBottom, isHorizontal:boolean)  => {
    let output;
    window.console.log("radiusStyles", radiusStyles);
    if (radiusStyles !== undefined) {
        const dataIsObject = typeof radiusStyles === "object";
        const keyA = isHorizontal ? horizontalKeys.LEFT : verticalKeys.TOP;
        const keyB = isHorizontal ? horizontalKeys.RIGHT : verticalKeys.BOTTOM;
        output = {
            [keyA]: dataIsObject ? radiusStyles[isHorizontal ? "left" : "top"] : radiusStyles,
            [keyB]: dataIsObject ? radiusStyles[isHorizontal ? "right" : "bottom"] : radiusStyles,
        };
    }
    return output;
};
