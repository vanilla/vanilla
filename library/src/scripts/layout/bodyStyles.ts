/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, viewHeight } from "csx";
import { cssRule } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { homePageVariables } from "@library/layout/homePageStyles";
import isEmpty from "lodash/isEmpty";
import { css, CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useEffect } from "react";

export const bodyStyleMixin = useThemeCache(() => {
    const globalVars = globalVariables();

    const style: CSSObject = {
        background: ColorsUtils.colorOut(globalVars.body.backgroundImage.color),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
            family: globalVars.fonts.families.body,
            color: globalVars.mainColors.fg,
        }),
        wordBreak: "break-word",

        "h1, h2, h3, h4, h5, h6": {
            lineHeight: globalVars.lineHeights.condensed,
            color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
        },
    };

    return style;
});

export const useBodyClass = () => {
    const globals = globalVariables();
    useEffect(() => {
        const bodyClass = css({ ...bodyStyleMixin(), label: "vanillaBodyReset" });
        document.body.classList.add(bodyClass);

        return function cleanup() {
            document.body.classList.remove(bodyClass);
        };
    }, [globals]);
};

export const globalCSS = useThemeCache(() => {
    cssRule("html", {
        msOverflowStyle: "-ms-autohiding-scrollbar",
    });

    cssRule("*", {
        // For Mobile Safari -> https://developer.mozilla.org/en-US/docs/Web/CSS/overscroll-behavior
        WebkitOverflowScrolling: "touch",
    });

    cssRule("h1, h2, h3, h4, h5, h6", {
        display: "block",
        ...Mixins.margin({
            all: 0,
        }),
        ...Mixins.padding({
            all: 0,
        }),
    });

    cssRule("p", {
        ...Mixins.margin({
            all: 0,
        }),
        ...Mixins.padding({
            all: 0,
        }),
    });

    cssRule(".page", {
        display: "flex",
        overflow: "visible",
        flexDirection: "column",
        width: percent(100),
        minHeight: viewHeight(100),
        position: "relative",
        zIndex: 0,
    });

    cssRule("button", {
        WebkitAppearance: "none",
        MozAppearance: "none",
    });

    cssRule(".page-minHeight", {
        flexGrow: 1,
        display: "flex",
        flexDirection: "column",
    });

    cssRule(`input[type="number"]`, {
        WebkitAppearance: "none",
        MozAppearance: "textfield",
        ...{
            [`&::-webkit-inner-spin-button`]: {
                WebkitAppearance: "none",
                margin: 0,
            },
            [`&::-webkit-outer-spin-button`]: {
                WebkitAppearance: "none",
                margin: 0,
            },
        },
    });

    cssRule(
        `input::-webkit-search-decoration,
        input::-webkit-search-cancel-button,
        input::-webkit-search-results-button,
        input::-webkit-search-results-decoration,
        input::-ms-clear`,
        {
            display: "none",
        },
    );
});

export const fullBackgroundClasses = useThemeCache((isRootPage = false) => {
    const globalVars = globalVariables();
    const style = styleFactory("fullBackground");
    const image = globalVars.body.backgroundImage;
    const homePageVars = homePageVariables();
    const source = isRootPage && !isEmpty(homePageVars.backgroundImage) ? homePageVariables() : globalVars.body;

    const root = style(
        {
            display: !image ? "none" : "block",
            position: "fixed",
            top: 0,
            left: 0,
            width: percent(100),
            height: viewHeight(100),
            zIndex: -1,
        },
        Mixins.background(source.backgroundImage),
    );

    return { root };
});
