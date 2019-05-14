/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, unit } from "@library/styles/styleHelpers";
import { lineHeightAdjustment } from "@library/styles/textUtils";

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
        alignItems: "center",
        lineHeight: vars.font.lineHeight,
    });

    const main = style("main", {
        ...lineHeightAdjustment(vars.font.lineHeight),
        position: "relative",
        display: "block",
        width: percent(100),
        flexGrow: 1,
    });

    const titleBar = style("titleBar", {
        display: "flex",
        position: "relative",
        alignItems: "center",
    });

    const paragraph = style("paragraph", {
        fontSize: unit(globalVars.fonts.size.small),
        marginBottom: unit(12),
    });

    const actions = style("actions", {
        display: "flex",
        marginLeft: unit(vars.cta.margin),
        position: "relative",
    });

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

    return {
        root,
        main,
        titleBar,
        paragraph,
        actions,
        link,
        titleWrap,
        actionButton,
    };
});

export const pageTitleClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = pageHeadingVariables();
    const style = styleFactory("pageHeading");

    const root = style({
        ...lineHeightAdjustment(vars.font.lineHeight),
        lineHeight: vars.font.lineHeight,
        display: "block",
        ...margins({
            vertical: 0,
        }),
    });
    const pageSmallTitle = style("pageSmallTitle", {
        ...lineHeightAdjustment(vars.font.lineHeight),
        lineHeight: vars.font.lineHeight,
        fontSize: globalVars.fonts.size.smallTitle,
        fontWeight: globalVars.fonts.weights.bold,
        display: "block",
        ...margins({
            vertical: 0,
        }),
    });

    const underTitle = style("underTitle", {
        marginTop: unit(6),
    });

    return {
        root,
        pageSmallTitle,
        underTitle,
    };
});
