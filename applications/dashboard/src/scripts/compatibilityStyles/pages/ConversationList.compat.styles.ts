/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { conversationListVariables } from "@dashboard/compatibilityStyles/pages/ConversationList.variables";

export const conversationListCompatCSS = () => {
    const globalVars = globalVariables();
    const vars = conversationListVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "ConversationList");

    cssOut(`.Conversation .PhotoWrap`, {
        top: 0,
        left: 0,
    });

    cssOut(".Conversation .Bullet", {
        display: "inline-block",
    });
};
