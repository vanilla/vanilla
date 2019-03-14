/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { px } from "csx";
import { globalVariables } from "../../styles/globalStyleVars";
import { srOnly, userSelect } from "../../styles/styleHelpers";
import { layoutVariables } from "../../styles/layoutStyles";
import { vanillaHeaderVariables } from "../../headers/vanillaHeaderStyles";
import { styleFactory, useThemeCache } from "../../styles/styleUtils";

const backLinkClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("backLink");
    const headerVars = vanillaHeaderVariables();

    const root = style({
        ...userSelect(),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const link = style("link", {
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
        "label",
        {
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
});

export default backLinkClasses;
