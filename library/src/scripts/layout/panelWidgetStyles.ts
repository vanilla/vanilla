/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings } from "@library/styles/styleHelpers";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

export const panelWidgetClasses = useThemeCache(mediaQueries => {
    const globalVars = globalVariables();
    const style = styleFactory("panelWidget");

    const root = style({
        display: "flex",
        flexDirection: "column",
        position: "relative",
        width: percent(100),
        ...paddings({
            all: globalVars.widget.padding,
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
            ...mediaQueries({
                [LayoutTypes.TWO_COLUMNS]: {
                    oneColumnDown: {
                        ...paddings({
                            all: globalVars.widget.padding,
                        }),
                    },
                },
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: {
                        ...paddings({
                            all: globalVars.widget.padding,
                        }),
                    },
                },
            }).$nest,
        },
    });

    return { root: root + " panelWidget" };
});
