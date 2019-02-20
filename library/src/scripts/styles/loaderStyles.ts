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
} from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { percent, quote } from "csx";
import { PositionProperty } from "csstype";

export function loaderVariables(theme?: object) {
    const globalVars = globalVariables(theme);
    const themeVars = componentThemeVariables(theme, "loader");

    const fullPage: ISpinnerProps = {
        size: 100,
        thickness: 6,
        color: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("fullPage"),
    };

    const fixedSize: ISpinnerProps = {
        size: 32,
        thickness: 4,
        color: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("fixedSize"),
    };

    const medium: ISpinnerProps = {
        size: 20,
        thickness: 6,
        color: globalVars.mainColors.primary,
        ...themeVars.subComponentStyles("medium"),
    };

    return { fullPage, fixedSize, medium };
}

export function loaderClasses(theme?: object) {
    const vars = loaderVariables(theme);
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
}
