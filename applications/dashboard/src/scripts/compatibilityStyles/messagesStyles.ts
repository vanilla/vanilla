/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpersColors";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { absolutePosition, unit } from "@library/styles/styleHelpers";
import { metaContainerStyles } from "@library/styles/metasStyles";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { forumVariables } from "@library/forms/forumStyleVars";

export const messagesCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const formVars = forumVariables();
    const userPhotoVars = formVars.userPhoto;

    cssOut(`.DismissMessage`, {
        color: colorOut(globalVars.elementaryColors.black),
    });

    cssOut(
        `
        .Condensed.DataList .ItemContent.Conversation,
        .ConversationMessage,

        `,
        {
            paddingLeft: unit(userPhotoVars.sizing.medium + layoutVars.cell.paddings.horizontal),
        },
    );

    cssOut(`.Section-Conversation .MessageList .Message`, {
        paddingLeft: 0,
    });

    cssOut(`.Condensed.DataList .ItemContent.Conversation .Meta`, {
        ...metaContainerStyles(),
    });

    cssOut(`.DataList.Conversations .Author.Photo`, {
        ...absolutePosition.topLeft(layoutVars.cell.paddings.vertical, layoutVars.cell.paddings.horizontal),
    });

    cssOut(`.DataList.Conversations .Author.Photo .PhotoWrap`, {
        position: "static",
    });
};
