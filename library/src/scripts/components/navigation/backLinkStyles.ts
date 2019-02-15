/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { px } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, srOnly } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { layoutVariables } from "@library/styles/layoutStyles";
import vanillaHeaderStyles, { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";

export default function backLinkClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const mediaQueries = layoutVariables(theme).mediaQueries();
    const debug = debugHelper("backLink");
    const headerVars = vanillaHeaderVariables();

    const root = style({
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "flex-start",
        userSelect: "none",
        ...debug.name(),
    });

    const link = style({
        ...debug.name("link"),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "flex-start",
        color: "inherit",
        minWidth: globalVars.icon.sizes.default,
        maxHeight: px(headerVars.sizing.height),
        $nest: {
            "&:hover": {
                color: globalVars.mainColors.primary.toString(),
            },
        },
    });

    const label = style(
        {
            ...debug.name("label"),
            lineHeight: px(globalVars.icon.sizes.default),
            fontWeight: globalVars.fonts.weights.semiBold,
            whiteSpace: "nowrap",
            paddingLeft: px(12),
            paddingRight: globalVars.gutter.half,
        },
        mediaQueries.xs(srOnly()),
    );

    return {
        root,
        link,
        label,
    };
}
