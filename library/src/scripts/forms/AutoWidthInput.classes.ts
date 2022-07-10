/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { styleUnit } from "@library/styles/styleUnit";

import { useThemeCache } from "@library/styles/styleUtils";

export const autoWidthInputClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const themeInput = css({
        "&&": {
            padding: "0",
            border: "0",
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
                align: "center",
                lineHeight: styleUnit(18),
            }),
            borderBottom: `2px solid ${ColorsUtils.colorOut(globalVars.elementaryColors.transparent)}`,
        },

        "&&:focus": {
            outline: "none",
            borderBottom: `2px solid ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
        },
    });
    const hiddenInputMeasure = css({
        visibility: "hidden",
        opacity: 0,
        position: "absolute",
        zIndex: -100,
        width: "auto !important",
    });

    return {
        themeInput,
        hiddenInputMeasure,
    };
});
