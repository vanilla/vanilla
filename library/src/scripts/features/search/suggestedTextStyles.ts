/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/styleUtils";
import { paddings, fonts } from "@library/styles/styleHelpers";
import { percent } from "csx";
import { buttonResetMixin } from "@library/forms/buttonStyles";
import { NestedCSSProperties } from "typestyle/lib/types";

export const suggestedTextStyleHelper = useThemeCache((overwrites = {}) => {
    const globalVars = globalVariables();
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
            size: globalVars.fonts.size.large,
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
        },
    } as NestedCSSProperties;

    return {
        option,
    };
});
