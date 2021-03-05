/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { styleUnit } from "@library/styles/styleUnit";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { Mixins } from "@library/styles/Mixins";

export const messagesCSS = () => {
    const globalVars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const userPhotoVars = userPhotoVariables();

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

    cssOut(`.Condensed.DataList .ItemContent.Conversation .Excerpt a`, {
        textDecoration: "none",
    });

    cssOut(`.DataList.Conversations .Author.Photo`, {
        ...Mixins.absolute.topLeft(layoutVars.cell.paddings.vertical, layoutVars.cell.paddings.horizontal),
    });

    cssOut(`.DataList.Conversations .Author.Photo .PhotoWrap`, {
        position: "static",
    });
};
