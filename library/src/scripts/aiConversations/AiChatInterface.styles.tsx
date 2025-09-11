/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const aiChatStyles = useThemeCache(() => {
    const wrapper = css({
        height: "60vh",
        display: "flex",
        flexDirection: "column",
        overflow: "hidden",
    });

    const outerContainer = css({
        display: "flex",
        flexDirection: "column",
        borderRadius: "3px",
        marginBottom: "8px",
        flex: 1,
        overflow: "auto",
        padding: "16px",
    });

    const frameBody = css({
        background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.1)),
    });

    const message = css({
        margin: "10px 0",
        maxWidth: "85%",
        minWidth: "40%",
        padding: "10px",
        borderRadius: "20px",
        border: singleBorder(),

        "&.noBorder": {
            border: "none",
        },
    });

    const messageAssistant = css({
        alignSelf: "flex-start",
        background: ColorsUtils.colorOut(globalVariables().mainColors.bg),
        color: ColorsUtils.colorOut(globalVariables().mainColors.fg),
        borderBottomLeftRadius: "5px",
    });

    const messageHuman = css({
        background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.1)),
        alignSelf: "flex-end",
        borderBottomRightRadius: "5px",
    });

    const messageMeta = css({
        display: "flex",
        alignItems: "center",
        marginTop: "5px",
        gap: "5px",
        fontSize: metasVariables().font.size,
        fontWeight: metasVariables().font.weight,
        marginBottom: "10px",
    });

    const aiAssistantPhoto = css({
        width: "20px",
        height: "20px",
        borderRadius: "50%",
    });

    const reactionButtonContainer = css({
        display: "flex",
        justifyContent: "flex-start",
        gap: "5px",
        marginTop: "10px",

        "& button:focus": {
            border: "1px solid currentColor",
        },

        "& button:hover": {
            border: "1px solid currentColor",
        },
    });

    const reactionButtonActive = css({
        borderRadius: "5px",

        color: ColorsUtils.colorOut(globalVariables().mainColors.secondary),
        background: ColorsUtils.colorOut(globalVariables().getFgForBg(globalVariables().mainColors.secondary)),

        "&:focus": {
            color: ColorsUtils.colorOut(globalVariables().getFgForBg(globalVariables().mainColors.secondary)),
            background: "transparent",
        },

        "&:hover": {
            color: ColorsUtils.colorOut(globalVariables().getFgForBg(globalVariables().mainColors.secondary)),
            background: "transparent",
        },
    });

    const links = css({
        maxHeight: "85vh",
        lineHeight: "1.5",

        "& a[href]": {
            ...Mixins.font({
                ...globalVariables().fontSizeAndWeightVars("medium"),
                color: ColorsUtils.colorOut(globalVariables().mainColors.fg),
                lineHeight: metasVariables().font.lineHeight,
                textDecoration: "underline",
            }),

            "&.footerLink": {
                fontSize: globalVariables().fontSizeAndWeightVars("small").size,
                lineHeight: "1.15",
                color: "inherit",
            },
        },

        "& a[href]:hover": {
            textDecoration: "underline",
        },
    });

    const headerSubContainer = css({
        display: "flex",
        justifyContent: "space-between",
        flexGrow: 1,
    });

    const aiNotice = css({
        color: "#000000",
        border: singleBorder({
            color: "#000000",
        }),
        marginLeft: "5px",
    });

    const aiNoticeMessage = css({
        color: "inherit",
        marginLeft: "auto",

        "& span": {
            color: "inherit",
            borderColor: "inherit",
        },
    });

    // Override the default width of 400px in the message box styles (messagesDropdownContent in messageBox.classes.tsx)
    const messageBoxDropdownContents = css({
        "&&&": {
            width: "650px",
        },
    });

    const footerContainer = css({
        padding: "1em 0",

        width: "100%",
        display: "flex",
        justifyContent: "space-between",
        gap: "1em",
    });

    const footerMessage = css({
        flexShrink: 1,

        fontSize: globalVariables().fontSizeAndWeightVars("small").size,
        "& a.footerLink": {
            fontSize: globalVariables().fontSizeAndWeightVars("small").size,
        },
    });

    const askCommunityButton = css({
        flexBasis: "40%",
        flexGrow: 1,
        flexShrink: 0,
    });

    const errorMessage = css({
        textAlign: "right",
        margin: "0 16px",
        color: ColorsUtils.colorOut(globalVariables().messageColors.error.fg),
        fontSize: globalVariables().fontSizeAndWeightVars("small").size,
    });

    return {
        wrapper,
        outerContainer,
        message,
        messageAssistant,
        messageHuman,
        messageMeta,
        frameBody,
        aiAssistantPhoto,
        reactionButtonContainer,
        reactionButtonActive,
        links,
        headerSubContainer,
        aiNotice,
        aiNoticeMessage,
        messageBoxDropdownContents,
        footerContainer,
        footerMessage,
        askCommunityButton,
        errorMessage,
    };
});
