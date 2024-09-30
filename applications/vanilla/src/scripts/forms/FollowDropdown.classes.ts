/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { color } from "csx";

interface IFollowClassParams {
    isOpen: boolean;
    isFollowed: boolean;
    /** These can be overridden in the asset settings */
    borderRadius?: number;
    buttonColor?: string;
    textColor?: string;
    alignment?: "start" | "center" | "end";
}

export const followDropdownClasses = useThemeCache((params: IFollowClassParams) => {
    const { isOpen, borderRadius, buttonColor, textColor, alignment } = params;
    const globalVars = globalVariables();

    const layout = css({
        // Alignment is only set in custom layouts but other layout styles are required for legacy layouts
        ...(alignment
            ? {
                  display: "flex",
                  justifyContent: alignment,
              }
            : {
                  marginLeft: "auto",
                  marginBottom: globalVars.spacer.size,
              }),
    });

    const followButton = css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        gap: globalVars.spacer.componentInner / 2,
        borderRadius: globalVars.border.radius,
        // Override from props
        ...(borderRadius && {
            borderRadius: `${borderRadius}px`,
            "&:not([disabled]):hover, &:not([disabled]):active, &:not([disabled]):focus": {
                borderRadius: `${borderRadius}px`,
            },
        }),
        // These fight with the legacy page css
        "&&": {
            padding: `5px ${globalVars.spacer.componentInner}px`,
            backgroundColor: isOpen ? ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)) : "transparent",
            // Override from props
            ...(buttonColor && {
                borderColor: buttonColor,
                backgroundColor: isOpen ? ColorsUtils.colorOut(color(buttonColor).fade(0.1)) : "transparent",
                "&:not([disabled]):hover, &:not([disabled]):active, &:not([disabled]):focus": {
                    borderColor: buttonColor,
                    backgroundColor: buttonColor,
                },
            }),
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            // Override from props
            ...(textColor && {
                color: textColor,
            }),
        },
    });

    const preferencesButton = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
    });

    const heading = css({
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const checkBox = css({
        paddingLeft: 0,
        paddingBottom: 4,
        "& > span": {
            fontWeight: "normal",
        },
    });

    const fullWidth = css({
        width: "100%",
    });

    const inset = css({
        marginLeft: 26,
    });

    const errorBlock = css({
        paddingLeft: 7,
    });

    const unClickable = css({
        pointerEvents: "none",
    });

    return {
        layout,
        followButton,
        preferencesButton,
        heading,
        checkBox,
        fullWidth,
        inset,
        errorBlock,
        unClickable,
    };
});
