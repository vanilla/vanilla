/*
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { objectFitWithFallback } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { CSSObject } from "@emotion/css";
import { important, percent } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const userPhotoVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("userPhoto", forcedVars);
    const globalVars = globalVariables();

    const border = makeThemeVars(
        "border",
        Variables.border({
            radius: "50%",
            width: 1,
            color: globalVars.mixBgAndFg(0.5).fade(0.3),
        }),
    );

    const sizing = makeThemeVars("sizing", {
        small: 28,
        medium: 40,
        large: 100,
        xlarge: 145,
    });

    return { border, sizing };
});

export const userPhotoMixins = (vars = userPhotoVariables()) => {
    // wrapper of image
    const root: CSSObject = {
        position: "relative",
        overflow: "hidden",
        ...Mixins.border(vars.border),
    };

    const photo = {
        ...objectFitWithFallback(),
        padding: important(0),
        margin: important(0),
        ...{
            "&&": {
                width: percent(100),
                height: "auto",
            },
        },
    };

    const small = {
        width: styleUnit(vars.sizing.small),
        height: styleUnit(vars.sizing.small),
        flexBasis: styleUnit(vars.sizing.small),
    };

    const medium = {
        width: styleUnit(vars.sizing.medium),
        height: styleUnit(vars.sizing.medium),
        flexBasis: styleUnit(vars.sizing.medium),
    };

    const large = {
        width: styleUnit(vars.sizing.large),
        height: styleUnit(vars.sizing.large),
        flexBasis: styleUnit(vars.sizing.large),
    };

    const xlarge = {
        width: styleUnit(vars.sizing.xlarge),
        height: styleUnit(vars.sizing.xlarge),
        flexBasis: styleUnit(vars.sizing.xlarge),
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

    const root = style(mixinStyles.root as CSSObject);
    const photo = style("photo", mixinStyles.photo);
    const small = style("small", mixinStyles.small);
    const medium = style("medium", mixinStyles.medium);
    const large = style("large", mixinStyles.large);
    const xlarge = style("large", mixinStyles.xlarge);

    const noPhoto = style("noPhoto", {
        display: "block",
        ...{
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
