/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { Mixins } from "@library/styles/Mixins";

export const panelWidgetClasses = useThemeCache((mediaQueries) => {
    const globalVars = globalVariables();
    const style = styleFactory("panelWidget");

    const root = style({
        display: "flex",
        flexDirection: "column",
        position: "relative",
        width: percent(100),
        ...Mixins.padding({
            all: globalVars.widget.padding,
        }),
        ...{
            "&.hasNoVerticalPadding": {
                ...Mixins.padding({ vertical: 0 }),
            },
            "&.hasNoHorizontalPadding": {
                ...Mixins.padding({ horizontal: 0 }),
            },
            "&.isSelfPadded": {
                ...Mixins.padding({ all: 0 }),
            },
            ...mediaQueries({
                [LayoutTypes.TWO_COLUMNS]: {
                    oneColumnDown: {
                        ...Mixins.padding({
                            all: globalVars.widget.padding,
                        }),
                    },
                },
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: {
                        ...Mixins.padding({
                            all: globalVars.widget.padding,
                        }),
                    },
                },
            }),
        },
    });

    return { root: root + " panelWidget" };
});
