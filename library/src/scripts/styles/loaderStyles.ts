/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    componentThemeVariables,
    debugHelper,
    flexHelper,
    ISpinnerProps,
    spinnerLoader,
    toStringColor,
} from "@library/styles/styleHelpers";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { style } from "typestyle";

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

    const fixedSize: ISpinnerProps = makeThemeVars("fixedSize", {
        size: 32,
        thickness: 4,
        color: colors.fg,
    });

    const medium: ISpinnerProps = makeThemeVars("medium", {
        size: 20,
        thickness: 6,
        color: colors.fg,
    });

    return { fullPage, fixedSize, medium };
});

export const loaderClasses = useThemeCache(() => {
    const vars = loaderVariables();
    const debug = debugHelper("loader");
    const flex = flexHelper();
    const fullPageLoader = style({
        ...debug.name("fullPageLoader"),
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
    const mediumLoader = style({
        ...debug.name("mediumLoader"),
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
    const fixedSizeLoader = style({
        ...debug.name("fixedSizeLoader"),
        ...flex.middle(),
        height: percent(100),
        width: percent(100),
        $nest: {
            "&:after": {
                ...spinnerLoader(vars.fixedSize),
            },
        },
    });

    return { fullPageLoader, mediumLoader, fixedSizeLoader };
});
