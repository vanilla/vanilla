/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { Mixins } from "@library/styles/Mixins";
import { css } from "@emotion/css";

export const panelWidgetClasses = useThemeCache((mediaQueries) => {
    const globalVars = globalVariables();

    const root = css({
        clear: "both",
        position: "relative",
        width: percent(100),
        ...Mixins.padding({
            all: globalVars.widget.padding,
        }),
        ":first-child > &": {
            paddingTop: 0,
        },
        ":last-child > &": {
            paddingBottom: 0,
        },
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
            [SectionTypes.TWO_COLUMNS]: {
                oneColumnDown: {
                    ...Mixins.padding({
                        all: globalVars.widget.padding,
                    }),
                },
            },
            [SectionTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    ...Mixins.padding({
                        all: globalVars.widget.padding,
                    }),
                },
            },
        }),
    });

    return { root: root + " panelWidget" };
});
