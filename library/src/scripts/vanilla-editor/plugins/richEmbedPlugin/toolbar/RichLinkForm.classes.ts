/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const richLinkFormClasses = useThemeCache(() => {
    const separator = css({
        margin: "16px -16px 12px",
    });

    const addLinkButton = css({
        display: "block",
        marginLeft: "auto",
    });

    const buttonTypeRadioGroup = css({
        display: "flex",
        flexDirection: "column",
    });

    const buttonTypeRadioOption = css({
        cursor: "pointer",
        padding: 8,
        paddingLeft: 16,
        paddingRight: 16,
        marginLeft: -16,
        marginRight: -16,
        "&:hover, &:active, &.focus-visible": {
            backgroundColor: ColorsUtils.colorOut(globalVariables().states.hover.highlight),
        },
        "&.isSelected": {
            color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
        },
    });

    return { separator, addLinkButton, buttonTypeRadioOption, buttonTypeRadioGroup };
});
