/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, flexHelper, ISpinnerProps, spinnerLoader, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { TLength } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";

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
        position: "fixed",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        margin: "auto",
        height: unit(vars.fullPage.size),
        width: unit(vars.fullPage.size),
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
