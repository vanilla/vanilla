/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/metas/Metas.variables";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { CSSObject } from "@emotion/css";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { Mixins } from "@library/styles/Mixins";
import { userSelect } from "@library/styles/styleHelpers";

export const suggestedTextStyleHelper = useThemeCache((overwrite?: { forDashboard?: boolean }) => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const { forDashboard = false } = overwrite || {};
    const mediaQueries = oneColumnVariables().mediaQueries();

    const baseStyle: CSSObject = {
        width: percent(100),
        ...Mixins.padding({
            vertical: 6,
            horizontal: 12,
        }),
        ...Mixins.font({
            ...(forDashboard ? globalVars.fontSizeAndWeightVars("medium") : globalVars.fontSizeAndWeightVars("large")),
            color: globalVars.mainColors.fg,
        }),
        textAlign: "left",
        display: "block",
        color: "inherit",
    };

    const groupHeading: CSSObject = {
        ...baseStyle,
        ...userSelect("none"),
        ...Mixins.font({
            size: 18,
            lineHeight: 24 / 18,
            weight: 700,
        }),
    };

    // The styles have been split here so they can be exported to the compatibility styles.
    const option: CSSObject = {
        ...buttonResetMixin(),

        ...baseStyle,

        ...{
            ".suggestedTextInput-parentTag": {
                ...Mixins.font({
                    ...metasVars.font,
                    lineHeight: "inherit",
                }),
            },
            "&:hover, &:focus, &.isFocused": {
                color: "inherit",
                backgroundColor: globalVars.states.hover.highlight.toString(),
            },
            "&": {
                ...mediaQueries.xs({
                    ...Mixins.padding({
                        vertical: 10,
                    }),
                }),
            },
        },
    };

    return {
        groupHeading,
        option,
    };
});
