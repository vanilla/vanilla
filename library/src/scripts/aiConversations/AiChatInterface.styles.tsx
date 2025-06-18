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

export const aiChatStyles = useThemeCache(() => {
    const outerContainer = css({
        display: "flex",
        flexDirection: "column",
    });

    const message = css({
        margin: "10px 0",
        maxWidth: "65%",
        padding: "10px",
        borderRadius: "5px",
    });

    const messageAssistant = css({
        alignSelf: "flex-start",
        background: "#EFF2F5",
        color: "black",
    });

    const messageHuman = css({
        alignSelf: "flex-end",
        color: ColorsUtils.colorOut(globalVariables().mainColors.secondaryContrast),
        background: ColorsUtils.colorOut(globalVariables().mainColors.secondary),
    });

    const reactionButtonContainer = css({
        display: "flex",
        justifyContent: "flex-start",
        gap: "5px",
        marginTop: "10px",
    });

    const reactionButtonActive = css({
        color: ColorsUtils.colorOut(globalVariables().mainColors.secondaryContrast),
        background: ColorsUtils.colorOut(globalVariables().mainColors.secondary),
        borderRadius: "5px",
    });

    const reactionButtonInActive = css({
        color: "auto",
        background: "transparent",
    });

    const links = css({
        "& a[href]": {
            ...Mixins.font({
                ...globalVariables().fontSizeAndWeightVars("medium"),
                color: globalVariables().mainColors.primary,
                lineHeight: metasVariables().font.lineHeight,
            }),
        },

        "& a[href]:hover": {
            textDecoration: "underline",
        },
    });

    const fab = css({
        display: "block",
        color: ColorsUtils.colorOut(globalVariables().mainColors.secondaryContrast),
    });

    return {
        outerContainer,
        message,
        messageAssistant,
        messageHuman,
        reactionButtonContainer,
        reactionButtonActive,
        reactionButtonInActive,
        links,
        fab,
    };
});
