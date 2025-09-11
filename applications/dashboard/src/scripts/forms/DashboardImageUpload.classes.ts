/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import type { CSSObject } from "@emotion/css/create-instance";
import { buttonClasses } from "@library/forms/Button.styles";
import { inputMixin, inputVariables } from "@library/forms/inputStyles";
import { useThemeCache } from "@library/styles/themeCache";

export const dashboardImageUploadClasses = useThemeCache(() => {
    const common: CSSObject = {
        paddingTop: 0,
        paddingBottom: 0,
        minHeight: 0,
        lineHeight: "calc(var(--height) - 2px)",
        height: "var(--height)",
        fontSize: "inherit",
    };

    return {
        fileUpload: css({
            position: "relative",
            display: "inline-flex",
            width: "100%",
            fontWeight: "normal",
            cursor: "pointer",
            alignItems: "center",
            fontSize: inputVariables().font.size,
            "--height": "36px",
            minHeight: "var(--height)",
            margin: 0,

            "&.isCompact": {
                "--height": "32px",
                fontSize: 13,
            },
        }),
        browse: cx(
            buttonClasses().standard,
            css({
                ...common,
                position: "absolute",
                top: 0,
                right: 0,
                borderRadius: 6,
                paddingLeft: 12,
                paddingRight: 12,
                width: "80px",
                lineHeight: "calc(var(--height) - 2px)",
                height: "var(--height)",
                minHeight: "var(--height)",
                minWidth: 0,
                "&&&": {
                    borderBottomLeftRadius: 0,
                    borderTopLeftRadius: 0,
                },
            }),
        ),
        choose: css({
            ...inputMixin(),
            ...common,
            position: "absolute",
            top: 0,
            right: 0,
            left: 0,
            minHeight: "var(--height)",

            maxWidth: "calc(100% - 79px)",
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
            "&&&": {
                borderTopRightRadius: 0,
                borderBottomRightRadius: 0,
            },
            ".isCompact &": {
                paddingLeft: 8,
                paddingRight: 8,
            },
            zIndex: 1,
        }),
        input: css({
            margin: 0,
            opacity: 0,
            height: 0,
            width: 0,
            maxHeight: 0,
            overflow: "hidden",
        }),
    };
});
