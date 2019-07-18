/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { em, percent, px } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, margins, unit } from "@library/styles/styleHelpers";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { NestedCSSProperties, NestedCSSSelectors } from "typestyle/lib/types";

export const pageHeadingVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("pageHeading");

    const font = makeThemeVars("font", {
        lineHeight: globalVars.lineHeights.condensed,
    });

    const cta = makeThemeVars("cta", {
        margin: "1em",
    });

    const dropDown = makeThemeVars("dropDown", {
        width: 150,
    });

    return {
        font,
        cta,
        dropDown,
    };
});

export const pageHeadingClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = pageHeadingVariables();
    const style = styleFactory("pageHeading");

    const root = style({
        display: "flex",
        width: percent(100),
        lineHeight: vars.font.lineHeight,
        alignItems: "flex-start",
    });

    const main = style("main", {
        display: "flex",
        flexWrap: "nowrap",
        position: "relative",
        width: percent(100),
        flexGrow: 1,
    });

    const titleBar = style("titleBar", {
        display: "flex",
        position: "relative",
        alignItems: "center",
    });

    const actions = (fontSize?: number | null) => {
        return style(
            "actions",
            {
                display: "flex",
                marginLeft: unit(vars.cta.margin),
                position: "relative",
                alignSelf: "flex-start",
                zIndex: 1,
            },
            fontSize
                ? {
                      top: ".5em",
                      fontSize: unit(fontSize),
                      transform: `translateY(-50%)`,
                  }
                : {},
        );
    };

    const link = style("link", {
        display: "block",
        height: unit(globalVars.icon.sizes.default),
        width: unit(globalVars.icon.sizes.default),
        color: "inherit",
    });

    const titleWrap = style("titleWrap", {
        position: "relative",
    });

    const actionButton = style("actionIcon", {
        width: px(20),
        height: px(20),
    });

    const lineHeightCentering = (lineHeight: number) => {
        // px value of line height
        return style("lineHeightCentering", {
            top: unit(lineHeight / 2),
            transform: `translateY(-50%)`,
        });
    };

    return {
        root,
        main,
        titleBar,
        actions,
        link,
        titleWrap,
        actionButton,
        lineHeightCentering,
    };
});

export const pageTitleClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = pageHeadingVariables();
    const style = styleFactory("pageTitle");

    const root = style({
        lineHeight: vars.font.lineHeight,
        display: "block",
        ...margins({
            vertical: 0,
        }),
        $nest: lineHeightAdjustment(),
    } as NestedCSSProperties);

    const pageSmallTitle = style("pageSmallTitle", {
        $nest: lineHeightAdjustment(),
        lineHeight: vars.font.lineHeight,
        fontSize: globalVars.fonts.size.smallTitle,
        fontWeight: globalVars.fonts.weights.bold,
        display: "block",
        ...margins({
            vertical: 0,
        }),
    } as NestedCSSProperties);

    return {
        root,
        pageSmallTitle,
    };
});
