/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, colorOut, margins, negative, srOnly, unit, userSelect } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { px } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { media } from "typestyle";

const backLinkVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("backLink");

    const sizing = makeThemeVars("backLink", {
        height: globalVars.icon.sizes.default,
        width: (globalVars.icon.sizes.default * 12) / 21, // From SVG ratio
    });

    return {
        sizing,
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
        height: px(titleBarVars.sizing.height),
        $nest: {
            "&:hover, &:focus": {
                color: colorOut(globalVars.mainColors.primary),
                outline: 0,
            },
        },
    });

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
        height: globalVars.icon.sizes.default,
        width: (globalVars.icon.sizes.default * 12) / 21, // From SVG ratio
    });

    return {
        root,
        link,
        label,
        icon,
    };
});

export default backLinkClasses;
