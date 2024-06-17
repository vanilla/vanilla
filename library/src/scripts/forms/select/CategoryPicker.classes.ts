/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";
import { colorOut } from "@library/styles/styleHelpers";

export const categoryPickerClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const pickerWrapper = css({
        position: "relative",
        ...Mixins.margin({ vertical: 16 }),
    });

    const pickerButton = css({
        "&&": {
            display: "inline-flex",
            cursor: "pointer",
        },
    });

    const select = css({
        "&&": {
            ...Mixins.absolute.fullSizeOfParent(),
            background: "transparent",
            border: "none",
            outline: "none",
            appearance: "none",
            color: "transparent",
            opacity: 0,
            zIndex: 1,
            cursor: "pointer",
        },

        "& option": {
            color: "initial",
        },
        ...{
            [`
                &:hover + .${pickerButton},
                &:focus + .${pickerButton},
                &:active + .${pickerButton},
                &.focus-visible + .${pickerButton},
            `]: {
                borderColor: colorOut(globalVars.mainColors.primary),
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const categoryLabel = css({
        ...Mixins.font({
            weight: globalVars.fonts.weights.bold,
            color: colorOut(globalVars.mainColors.fg),
        }),
    });

    const categoryDescription = css({
        ...Mixins.font({
            size: globalVars.fonts.size.large,
        }),
    });

    const categoryInfo = css({
        ...Mixins.padding({ bottom: 16, top: 8 }),
    });

    return {
        pickerWrapper,
        pickerButton,
        select,
        categoryLabel,
        categoryDescription,
        categoryInfo,
    };
});
