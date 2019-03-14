/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "../styles/globalStyleVars";
import {
    absolutePosition,
    componentThemeVariables,
    debugHelper,
    flexHelper,
    ISpinnerProps,
    spinnerLoader,
    colorOut,
    unit,
} from "../styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "../styles/styleUtils";
import { percent } from "csx";
import { TLength } from "typestyle/lib/types";

export const loaderVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("loader");

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.primary,
    });

    const fullPage: ISpinnerProps = makeThemeVars("fullPage", {
        size: 100,
        thickness: 6,
        color: colors.fg,
    });

    const medium: ISpinnerProps = makeThemeVars("medium", {
        size: 50,
        thickness: 4,
        color: colors.fg,
    });

    const small: ISpinnerProps = makeThemeVars("small", {
        size: 36,
        thickness: 4,
        color: colors.fg,
    });

    return { fullPage, small, medium };
});

export const loaderClasses = useThemeCache(() => {
    const vars = loaderVariables();
    const flex = flexHelper();
    const style = styleFactory("loader");
    const fullPageLoader = style("fullPageLoader", {
        ...flex.middle(),
        position: "fixed",
        top: 0,
        left: 0,
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": {
                ...spinnerLoader(vars.fullPage),
            },
        },
    });
    const mediumLoader = style("mediumLoader", {
        ...absolutePosition.fullSizeOfParent(),
        ...flex.middle(),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": {
                ...spinnerLoader(vars.medium),
            },
        },
    });
    const smallLoader = style("smallLoader", {
        ...flex.middle(),
        height: percent(46),
        width: percent(46),
        margin: "auto",
        $nest: {
            "&:after": {
                ...spinnerLoader(vars.small),
            },
        },
    });

    const loaderContainer = (size: TLength) => {
        return style({
            position: "relative",
            display: "block",
            margin: "auto",
            height: unit(size),
            width: unit(size),
        });
    };

    return {
        fullPageLoader,
        mediumLoader,
        smallLoader,
        loaderContainer,
    };
});
