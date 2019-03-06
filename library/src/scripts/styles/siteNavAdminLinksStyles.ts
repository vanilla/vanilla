/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper, unit } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { layoutVariables } from "@library/styles/layoutStyles";
import { px } from "csx";

export function siteNavAdminLinksClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const debug = debugHelper("siteNavAdminLinks");
    const mediaQueries = layoutVariables(theme).mediaQueries();

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
}
