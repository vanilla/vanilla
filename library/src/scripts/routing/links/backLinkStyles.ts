/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, colorOut, margins, negative, srOnly, unit, userSelect } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { important, px } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { media } from "typestyle";
import { lineHeightAdjustment } from "@library/styles/textUtils";

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
            height: unit(vars.sizing.height),
            minWidth: unit(vars.sizing.width),
            ...margins({
                left: negative(vars.sizing.width + globalVars.gutter.half),
                right: globalVars.gutter.half,
            }),
        },
        mediaQueries.oneColumnDown({
            ...margins({
                left: 0,
            }),
        }),
    );

    const link = style("link", {
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "flex-start",
        color: "inherit",
        height: unit(vars.sizing.height),
        $nest: {
            "&:hover, &:focus": {
                color: colorOut(globalVars.mainColors.primary),
                outline: 0,
            },
        },
    });

    const inHeading = (fontSize?: number | null) => {
        if (fontSize) {
            return style("inHeading", {
                ...absolutePosition.topLeft(".5em"),
                fontSize: unit(fontSize),
                transform: `translateY(-50%)`,
                marginTop: unit(vars.position.verticalOffset),
            });
        } else {
            return "";
        }
    };

    const label = style(
        "label",
        {
            lineHeight: px(globalVars.icon.sizes.default),
            fontWeight: globalVars.fonts.weights.semiBold,
            whiteSpace: "nowrap",
            paddingLeft: px(12),
        },
        mediaQueries.xs(srOnly()),
    );

    const icon = style("icon", {
        height: unit(vars.sizing.height),
        width: unit(vars.sizing.width),
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
