/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/styleUtils";
import { paddings, fonts, unit } from "@library/styles/styleHelpers";
import { calc, percent, translateX, viewWidth } from "csx";
import { buttonResetMixin } from "@library/forms/buttonStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const suggestedTextStyleHelper = useThemeCache((overwrite?: { forDashboard?: boolean }) => {
    const globalVars = globalVariables();
    const { forDashboard = false } = overwrite || {};
    const mediaQueries = layoutVariables().mediaQueries();

    // The styles have been split here so they can be exported to the compatibility styles.
    const option = {
        ...buttonResetMixin(),
        width: percent(100),
        ...paddings({
            vertical: 6,
            horizontal: 12,
        }),
        ...fonts({
            color: globalVars.mainColors.fg,
            size: forDashboard ? globalVars.fonts.size.medium : globalVars.fonts.size.large,
        }),
        textAlign: "left",
        display: "block",
        color: "inherit",
        $nest: {
            "& .suggestedTextInput-parentTag": {
                ...fonts({
                    ...globalVars.meta.text,
                    lineHeight: "inherit",
                }),
            },
            "&:hover, &:focus, &.isFocused": {
                color: "inherit",
                backgroundColor: globalVars.states.hover.highlight.toString(),
            },
            "&": {
                ...mediaQueries.xs({
                    ...paddings({
                        vertical: 10,
                    }),
                }),
            },
        },
    } as NestedCSSProperties;

    return {
        option,
    };
});
