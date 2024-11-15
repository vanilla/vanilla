/*
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { objectFitWithFallback } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { css } from "@emotion/css";
import { CSSObject } from "@emotion/css/types/create-instance";
import { important, percent } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSProperties } from "react";

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
        xsmall: 24,
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
        verticalAlign: "initial",
        ...{
            "&&": {
                width: percent(100),
                height: "auto",
            },
        },
    };

    const sizeFn = (size: any) => {
        return {
            width: styleUnit(size),
            height: styleUnit(size),
            flexBasis: styleUnit(size),
        };
    };

    return {
        root,
        photo,
        sizeFn,
    };
};

export const userPhotoClasses = useThemeCache(() => {
    const vars = userPhotoVariables();

    // I'm doing this so we can import the styles in the compatibility styles.
    const mixinStyles = userPhotoMixins(vars);

    const root = css(mixinStyles.root);
    const photo = css(mixinStyles.photo);
    const xsmall = css(mixinStyles.sizeFn(vars.sizing.xsmall));
    const small = css(mixinStyles.sizeFn(vars.sizing.small));
    const medium = css(mixinStyles.sizeFn(vars.sizing.medium));
    const large = css(mixinStyles.sizeFn(vars.sizing.large));
    const xlarge = css(mixinStyles.sizeFn(vars.sizing.xlarge));

    const noPhoto = css({
        display: "block",
        ...{
            "&&": {
                width: percent(100),
            },
        },
    });

    return {
        root,
        xsmall,
        small,
        medium,
        large,
        xlarge,
        photo,
        noPhoto,
    };
});
