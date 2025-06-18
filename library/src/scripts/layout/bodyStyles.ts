/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, viewHeight, type ColorHelper } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { homePageVariables } from "@library/layout/homePageStyles";
import isEmpty from "lodash-es/isEmpty";
import { CSSObject, type CSSProperties } from "@emotion/serialize";
import { injectGlobal } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useEffect } from "react";
import { ColorVar } from "@library/styles/CssVar";
import { Variables } from "@library/styles/Variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { dropDownVariables } from "@library/flyouts/dropDownStyles";
import { inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { modalVariables } from "@library/modal/modalStyles";

export const bodyStyleMixin = useThemeCache((theme: "dark" | "light" = "light") => {
    const globalVars = globalVariables();

    const style: CSSObject = {
        background: ColorsUtils.varOverride(ColorVar.Background, globalVars.body.backgroundImage.color),
        lineHeight: 1.15,
        wordBreak: "break-word",

        ...colorDefinition(theme),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
            family: globalVars.fonts.families.body,
            color: ColorsUtils.varOverride(ColorVar.Foreground, globalVars.mainColors.fg),
        }),

        ":where(&) h1, h2, h3, h4, h5, h6": {
            ...Mixins.font({
                lineHeight: globalVars.lineHeights.condensed,
                color: ColorsUtils.varOverride(ColorVar.Foreground, globalVars.mainColors.fgHeading),
                family: globalVars.fonts.families.headings,
            }),
        },
    };

    return style;
});

export const useBodyCSS = () => {
    const globalVars = globalVariables.useAsHook();

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
                scroll-padding-top: 48px;
            }

            h1, h2, h3, h4, h5, h6 {
                line-height: ${globalVars.lineHeights.condensed};
                color: ${ColorsUtils.varOverride(ColorVar.Foreground, globalVars.mainColors.fgHeading)};
                font-family: ${globalVars.fonts.families.headings};
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

export function colorDefinition(theme: "dark" | "light"): CSSObject {
    const globalVars = globalVariables();
    const dropdownVars = dropDownVariables();
    const metaVars = metasVariables();
    const inputVars = inputVariables();
    const formElementVars = formElementsVariables();
    const modalVars = modalVariables();

    const lightVars: CSSObject = Variables.colorDefinition<ColorVar>({
        [ColorVar.Primary]: globalVars.mainColors.primary,
        [ColorVar.PrimaryContrast]: globalVars.mainColors.primaryContrast,
        [ColorVar.PrimaryState]: globalVars.mainColors.statePrimary,
        [ColorVar.Secondary]: globalVars.mainColors.secondary,
        [ColorVar.SecondaryState]: globalVars.mainColors.stateSecondary as unknown as ColorHelper,
        [ColorVar.SecondaryContrast]: globalVars.mainColors.secondaryContrast,
        [ColorVar.Background]: globalVars.mainColors.bg,
        [ColorVar.Background1]: globalVars.mainColors.bg,
        [ColorVar.Background2]: globalVars.mainColors.bg,
        [ColorVar.Foreground]: globalVars.mainColors.fg,
        [ColorVar.Yellow]: "#c9ae3f",
        [ColorVar.Red]: "#b1534e",
        [ColorVar.Green]: "#6aa253",
        [ColorVar.Meta]: metaVars.font.color,
        [ColorVar.DropdownBackground]: dropdownVars.contents.bg,
        [ColorVar.DropdownForeground]: dropdownVars.contents.fg,
        [ColorVar.Border]: globalVars.border.color,
        [ColorVar.HighlightBackground]: globalVars.states.hover.highlight,
        [ColorVar.HighlightForeground]: globalVars.states.hover.contrast ?? dropdownVars.contents.fg,
        [ColorVar.HighlightFocusBackground]: globalVars.states.focus.highlight,
        [ColorVar.HighlightFocusForeground]: globalVars.states.focus.contrast ?? dropdownVars.contents.fg,
        [ColorVar.InputBackground]: inputVars.colors.bg,
        [ColorVar.InputForeground]: inputVars.colors.fg,
        [ColorVar.InputBorder]: inputVars.border.color,
        [ColorVar.InputBorderActive]: inputVars.colors.state.fg,
        [ColorVar.InputPlaceholder]: formElementVars.placeholder.color,
        [ColorVar.InputTokenBackground]: ColorsUtils.modifyColorBasedOnLightness({
            color: inputVars.colors.bg,
            weight: 0.1,
        }),
        [ColorVar.InputTokenForeground]: metaVars.font.color,
        [ColorVar.ModalBackground]: modalVars.colors.bg,
        [ColorVar.ModalForeground]: modalVars.colors.fg,
        [ColorVar.Link]: globalVars.links.colors.default,
        [ColorVar.LinkActive]: globalVars.links.colors.active ?? globalVars.links.colors.default,
        "--vnla-component-inner-space": `${globalVars.spacer.componentInner}px`,
    });

    if (theme === "light") {
        return lightVars;
    } else {
        return {
            ...lightVars,
            ...Variables.colorOverride({
                [ColorVar.Background]: "#110E1B",
                [ColorVar.Foreground]: "#f8f8f2",
                [ColorVar.Background1]: "#1d1a26",
                [ColorVar.Background2]: "#0a0810",
                [ColorVar.InputBackground]: "#1d1a26",
                [ColorVar.InputForeground]: "#f8f8f2",
                [ColorVar.InputBorder]: "#44414b",
                [ColorVar.InputBorderActive]: "#55515b",
                [ColorVar.InputPlaceholder]: "rgba(255, 255, 255, 0.4)",
                [ColorVar.Border]: "#44414b",
                [ColorVar.Meta]: "#f8f8f2",
                [ColorVar.DropdownBackground]: ColorsUtils.var(ColorVar.Background),
                [ColorVar.DropdownForeground]: ColorsUtils.var(ColorVar.Foreground),
                [ColorVar.HighlightBackground]: ColorsUtils.var(ColorVar.Background1),
                [ColorVar.HighlightForeground]: ColorsUtils.var(ColorVar.Foreground),
                [ColorVar.Primary]: ColorsUtils.colorOut(globalVars.mainColors.primary.lighten(0.25)),
                [ColorVar.PrimaryContrast]: "#fff",
                [ColorVar.Link]: ColorsUtils.colorOut(globalVars.mainColors.primary.lighten(0.25)),
                [ColorVar.LinkActive]: ColorsUtils.colorOut(globalVars.mainColors.primary.lighten(0.18)),
                [ColorVar.InputTokenBackground]: ColorsUtils.var("#2e2840"),
                [ColorVar.InputTokenForeground]: ColorsUtils.var(ColorVar.Foreground),
            }),
        };
    }
}

export const globalCSS = useThemeCache((includeColorDefinition: boolean = true) => {
    injectGlobal({
        html: {
            msOverflowStyle: "-ms-autohiding-scrollbar",
        },
        ":root": includeColorDefinition ? colorDefinition("light") : undefined,
        "#titleBar": {
            display: "contents",
            zIndex: 10000,
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
        button: {
            WebkitAppearance: "none",
            MozAppearance: "none",
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
        Mixins.background({
            ...source.backgroundImage,
        }),
        {
            backgroundColor: ColorsUtils.varOverride(ColorVar.Background, source.backgroundImage.color),
        },
    );

    return { root };
});
