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
                marginLeft: styleUnit(vars.cta.margin),
                position: "relative",
                alignSelf: "flex-start",
                zIndex: 1,
            },
            fontSize
                ? {
                      top: ".5em",
                      fontSize: styleUnit(fontSize),
                      transform: `translateY(-50%)`,
                  }
                : {},
        );
    };

    const link = style("link", {
        display: "block",
        height: styleUnit(globalVars.icon.sizes.default),
        width: styleUnit(globalVars.icon.sizes.default),
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
            top: styleUnit(lineHeight / 2),
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
