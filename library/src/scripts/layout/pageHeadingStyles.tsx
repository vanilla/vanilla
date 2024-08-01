/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { em, percent, px } from "csx";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleUnit } from "@library/styles/styleUnit";
import { css } from "@emotion/css";

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

    const root = css({
        display: "flex",
        width: percent(100),
        lineHeight: vars.font.lineHeight,
        alignItems: "center",
    });

    const main = css({
        display: "flex",
        flexWrap: "nowrap",
        position: "relative",
        width: percent(100),
        flexGrow: 1,
    });

    const titleBar = css({
        display: "flex",
        position: "relative",
        alignItems: "center",
    });

    const titleWrap = css({
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        flex: 1,
    });

    const actions = css({
        display: "flex",
        alignItems: "center",

        "& > *": {
            marginLeft: globalVars.gutter.size,
        },
    });

    return {
        root,
        main,
        titleBar,
        titleWrap,
        actions,
    };
});
