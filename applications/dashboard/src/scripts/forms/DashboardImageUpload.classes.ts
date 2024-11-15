/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const dashboardImageUploadClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const fileUpload = css({
        "&.isCompact": {
            minHeight: 30,
            "& > input, & .file-upload-choose, & .file-upload-browse": {
                lineHeight: "28px",
                fontSize: 13,
                maxHeight: 30,
                borderColor: ColorsUtils.colorOut(globalVars.border.color),
                "&:focus, &:hover, &:active, &.focus-visible": {
                    borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                },
            },

            "& .file-upload-choose": {
                padding: "0 8px",
                maxWidth: "calc(100% - 63px)",
                overflow: "hidden",
                textOverflow: "ellipsis",
                whiteSpace: "nowrap",
                borderTopRightRadius: 0,
                borderBottomRightRadius: 0,
            },

            "& .file-upload-browse": {
                minWidth: 64,
                background: ColorsUtils.colorOut(globalVars.mainColors.bg),
                color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                "&:focus, &:hover, &:active, &.focus-visible": {
                    background: ColorsUtils.colorOut(globalVars.mainColors.primary),
                    borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                    color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
                },
            },
        },
    });

    return { fileUpload };
});
