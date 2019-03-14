/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "../styles/globalStyleVars";
import { layoutVariables } from "../styles/layoutStyles";
import { colorOut, debugHelper, unit } from "../styles/styleHelpers";
import { useThemeCache } from "../styles/styleUtils";
import { px } from "csx";
import { style } from "typestyle";

export const siteNavAdminLinksClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const debug = debugHelper("siteNavAdminLinks");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style(
        {
            display: "block",
            margin: 0,
            ...debug.name(),
        },
        mediaQueries.oneColumn({
            marginBottom: unit(25),
        }),
    );

    const item = style({
        display: "block",
        color: colorOut(globalVars.mainColors.fg),
        ...debug.name("item"),
    });

    const divider = style({
        borderBottom: `solid 1px ${globalVars.mixBgAndFg(0.5)}`,
        marginBottom: px(25),
        ...debug.name("i"),
    });

    const link = style({
        color: "inherit",
        fontWeight: globalVars.fonts.weights.semiBold,
        marginLeft: px(6),
        ...debug.name("link"),
    });

    return { root, item, divider, link };
});
