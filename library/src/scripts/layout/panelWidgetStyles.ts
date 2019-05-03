/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent } from "csx";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const panelWidgetClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = layoutVariables();
    const mediaQueries = vars.mediaQueries();
    const style = styleFactory("panelWidget");

    const root = style(
        {
            display: "flex",
            flexDirection: "column",
            position: "relative",
            width: percent(100),
            ...paddings({
                all: globalVars.gutter.half,
            }),
            $nest: {
                "&.hasNoVerticalPadding": {
                    ...paddings({ vertical: 0 }),
                },
                "&.hasNoHorizontalPadding": {
                    ...paddings({ horizontal: 0 }),
                },
                "&.isSelfPadded": {
                    ...paddings({ all: 0 }),
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                all: 8,
            }),
        }),
    );

    return { root };
});
