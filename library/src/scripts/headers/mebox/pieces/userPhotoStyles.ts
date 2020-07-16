/*
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { objectFitWithFallback, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { NestedCSSProperties } from "typestyle/lib/types";
import { important, percent } from "csx";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const userPhotoVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("userPhoto", forcedVars);

    const border = makeThemeVars("border", {
        radius: "50%",
    });

    const sizing = makeThemeVars("sizing", {
        small: 28,
        medium: 40,
        large: 100,
        xlarge: 145,
    });

    return { border, sizing };
});

export const userPhotoMixins = vars => {
    const root = {
        position: "relative",
        borderRadius: vars.border.radius,
        overflow: "hidden",
    } as NestedCSSProperties;

    const photo = {
        ...objectFitWithFallback(),
        padding: important(0),
        $nest: {
            "&&": {
                width: percent(100),
                height: "auto",
            },
        },
    } as NestedCSSProperties;

    const small = {
        width: unit(vars.sizing.small),
        height: unit(vars.sizing.small),
    } as NestedCSSProperties;

    const medium = {
        width: unit(vars.sizing.medium),
        height: unit(vars.sizing.medium),
    } as NestedCSSProperties;

    const large = {
        width: unit(vars.sizing.large),
        height: unit(vars.sizing.large),
    } as NestedCSSProperties;

    const xlarge = {
        width: unit(vars.sizing.xlarge),
        height: unit(vars.sizing.xlarge),
    };

    return {
        root,
        photo,
        small,
        medium,
        large,
        xlarge,
    };
};

export const userPhotoClasses = useThemeCache(() => {
    const vars = userPhotoVariables();
    const style = styleFactory("userPhoto");
    // I'm doing this so we can import the styles in the compatibility styles.
    const mixinStyles = userPhotoMixins(vars);

    const root = style(mixinStyles.root);
    const photo = style("photo", mixinStyles.photo);
    const small = style("small", mixinStyles.small);
    const medium = style("medium", mixinStyles.medium);
    const large = style("large", mixinStyles.large);
    const xlarge = style("large", mixinStyles.xlarge);

    const noPhoto = style("noPhoto", {
        display: "block",
        $nest: {
            "&&": {
                width: percent(100),
            },
        },
    });

    return {
        root,
        small,
        medium,
        large,
        xlarge,
        photo,
        noPhoto,
    };
});
