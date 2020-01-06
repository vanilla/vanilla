/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent } from "csx";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";
import { paddings } from "@library/styles/styleHelpers";
import { NestedCSSSelectors } from "typestyle/lib/types";

export const panelAreaClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = layoutVariables();
    const mediaQueries = vars.mediaQueries();
    const style = styleFactory("panelArea");
    const classesPanelWidget = panelWidgetClasses();

    const root = style(
        {
            width: percent(100),
            ...paddings({
                all: globalVars.gutter.half,
            }),
            $nest: {
                "& .heading": {
                    $nest: lineHeightAdjustment(),
                },
                [`&.inheritHeight > .${classesPanelWidget.root}`]: {
                    flexGrow: 1,
                },
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
                horizontal: 0,
            }),
        }),
    );

    return { root };
});
