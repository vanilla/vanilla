/*
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { debugHelper, objectFitWithFallback, unit } from "@library/styles/styleHelpers";
import { componentThemeVariables, useThemeCache } from "@library/styles/styleUtils";
import { style } from "typestyle";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const userPhotoVariables = useThemeCache(() => {
    const themeVars = componentThemeVariables("userPhoto");

    const border = {
        radius: "50%",
        ...themeVars.subComponentStyles("border"),
    };

    const sizing = {
        small: 28,
        medium: 40,
        large: 100,
        ...themeVars.subComponentStyles("sizing"),
    };

    return { border, sizing };
});

export const userPhotoClasses = useThemeCache(() => {
    const vars = userPhotoVariables();
    const debug = debugHelper("userPhoto");

    const root = style({
        ...debug.name(),
        position: "relative",
        borderRadius: vars.border.radius,
        overflow: "hidden",
    });

    const photo = style({
        ...objectFitWithFallback(),
        ...debug.name("photo"),
    });

    const small = style({
        width: unit(vars.sizing.small),
        height: unit(vars.sizing.small),
        ...debug.name("small"),
    });

    const medium = style({
        width: unit(vars.sizing.medium),
        height: unit(vars.sizing.medium),
        ...debug.name("medium"),
    });

    const large = style({
        width: unit(vars.sizing.large),
        height: unit(vars.sizing.large),
        ...debug.name("large"),
    });

    return { root, small, medium, large, photo };
});
