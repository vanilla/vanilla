/*!
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { important, px } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const deviceCheckerClasses = useThemeCache(() => {
    const style = styleFactory("deviceChecker");
    const queries = layoutVariables().mediaQueries();

    const root = style(
        {
            visibility: important("hidden"),
            height: important(0),
            margin: important(0),
            padding: important(0),
            width: px(4),
        },
        queries.noBleed({
            width: px(3),
        }),
        queries.twoColumns({
            width: px(2),
        }),
        queries.oneColumn({
            width: px(1),
        }),
        queries.xs({
            width: px(0),
        }),
    );

    return { root };
});
