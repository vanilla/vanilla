/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, viewHeight } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { homePageVariables } from "@library/layout/homePageStyles";
import isEmpty from "lodash/isEmpty";
import { CSSObject, injectGlobal } from "@emotion/css";
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

export const useBodyCSS = () => {
    const globalVars = globalVariables();

    useEffect(() => {
        const bodyStyle = bodyStyleMixin();
        const stylesheet = document.createElement("style");
        stylesheet.innerHTML = `
            body {
                background: ${bodyStyle.background};
                font-size: ${bodyStyle.fontSize};
                font-family: ${bodyStyle.fontFamily};
                color: ${bodyStyle.color};
                word-break: ${bodyStyle.wordBreak};
            }

            h1, h2, h3, h4, h5, h6 {
                line-height: ${globalVars.lineHeights.condensed};
                color: ${ColorsUtils.colorOut(globalVars.mainColors.fgHeading)};
            }
        `;

        document.head.insertBefore(
            stylesheet,
            document.head.querySelector("[data-emotion]") ?? document.head.firstChild,
        );

        return function cleanup() {
            document.head.removeChild(stylesheet);
        };
    }, [globalVars]);
};

export const globalCSS = useThemeCache(() => {
    injectGlobal({
        html: {
            msOverflowStyle: "-ms-autohiding-scrollbar",
        },
    });

    injectGlobal({
        "*": {
            // For Mobile Safari -> https://developer.mozilla.org/en-US/docs/Web/CSS/overscroll-behavior
            WebkitOverflowScrolling: "touch",
        },
    });

    injectGlobal({
        "h1, h2, h3, h4, h5, h6": {
            display: "block",
            ...Mixins.margin({
                all: 0,
            }),
            ...Mixins.padding({
                all: 0,
            }),
        },
    });

    injectGlobal({
        p: {
            ...Mixins.margin({
                all: 0,
            }),
            ...Mixins.padding({
                all: 0,
            }),
        },
    });

    injectGlobal({
        ".page": {
            display: "flex",
            overflow: "visible",
            flexDirection: "column",
            width: percent(100),
            minHeight: viewHeight(100),
            position: "relative",
            zIndex: 0,
        },
    });

    injectGlobal({
        button: {
            WebkitAppearance: "none",
            MozAppearance: "none",
        },
    });

    injectGlobal({
        ".page-minHeight": {
            flexGrow: 1,
            display: "flex",
            flexDirection: "column",
        },
    });

    injectGlobal({
        [`input[type="number"]`]: {
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
        },
    });
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
