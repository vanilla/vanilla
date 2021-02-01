/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { absolutePosition } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { metaContainerStyles } from "@library/styles/metasStyles";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { forumVariables } from "@library/forms/forumStyleVars";

export const messagesCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const formVars = forumVariables();
    const userPhotoVars = formVars.userPhoto;

    cssOut(`.DismissMessage`, {
        color: ColorsUtils.colorOut(globalVars.elementaryColors.black),
    });

    cssOut(
        `
        .Condensed.DataList .ItemContent.Conversation,
        .ConversationMessage,

        `,
        {
            paddingLeft: styleUnit(userPhotoVars.sizing.medium + layoutVars.cell.paddings.horizontal),
        },
    );

    cssOut(`.Section-Conversation .MessageList .Message`, {
        paddingLeft: 0,
    });

    cssOut(`.Condensed.DataList .ItemContent.Conversation .Meta`, {
        ...metaContainerStyles(),
    });

    cssOut(`.Condensed.DataList .ItemContent.Conversation .Excerpt a`, {
        textDecoration: "none",
    });

    cssOut(`.DataList.Conversations .Author.Photo`, {
        ...absolutePosition.topLeft(layoutVars.cell.paddings.vertical, layoutVars.cell.paddings.horizontal),
    });

    cssOut(`.DataList.Conversations .Author.Photo .PhotoWrap`, {
        position: "static",
    });
};
