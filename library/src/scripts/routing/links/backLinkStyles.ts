/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, negative, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important, percent, px, translateY } from "csx";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

const backLinkVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("backLink");

    const sizing = makeThemeVars("backLink", {
        height: globalVars.icon.sizes.default,
        width: (globalVars.icon.sizes.default * 12) / 21, // From SVG ratio
    });

    // We do a best guess based on calculations for the vertical position of the back link.
    // However, it might visually be a little off and need some adjustment
    const position = makeThemeVars("position", {
        verticalOffset: globalVars.fonts.alignment.headings.verticalOffsetForAdjacentElements,
    });

    return {
        sizing,
        position,
    };
});

const backLinkClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("backLink");
    const titleBarVars = titleBarVariables();
    const vars = backLinkVariables();

    const root = style(
        {
            ...userSelect(),
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-start",
            overflow: "visible",
            height: styleUnit(vars.sizing.height),
            minWidth: styleUnit(vars.sizing.width),
            ...Mixins.margin({
                left: negative(22),
                right: globalVars.gutter.half,
            }),
            transform: translateY("-0.1em"),
        },
        mediaQueries.oneColumnDown({
            ...Mixins.margin({
                left: 0,
            }),
        }),
    );

    const link = style("link", {
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "flex-start",
        color: "inherit",
        height: styleUnit(vars.sizing.height),
        ...{
            "&:hover, &:focus": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                outline: 0,
            },
        },
    });

    const inHeading = (fontSize?: number | null) => {
        if (fontSize) {
            return style("inHeading", {
                ...absolutePosition.topLeft(".5em"),
                fontSize: styleUnit(fontSize),
                transform: `translateY(-50%)`,
                marginTop: styleUnit(vars.position.verticalOffset),
            });
        } else {
            return "";
        }
    };

    const label = style(
        "label",
        {
            fontWeight: globalVars.fonts.weights.semiBold,
            whiteSpace: "nowrap",
            paddingLeft: px(12),
        },
        mediaQueries.xs(Mixins.absolute.srOnly()),
    );

    const icon = style("icon", {
        height: styleUnit(vars.sizing.height),
        width: styleUnit(vars.sizing.width),
    });

    // Since the back link needs to be outside the heading, we need a way to get the height of one line of text to center the link vertically.
    // We need to get the height from the text, so this element is a hidden space used for aligning.
    const getLineHeight = style("getLineHeight", {
        visibility: important("hidden"),
    });

    return {
        root,
        link,
        label,
        icon,
        getLineHeight,
        inHeading,
    };
});

export default backLinkClasses;
