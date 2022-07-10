/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { conversationVariables } from "@dashboard/compatibilityStyles/pages/Conversation.variables";
import { injectGlobal } from "@emotion/css";

export const conversationCompatCSS = () => {
    const vars = conversationVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "Conversation");
    MixinsFoundation.contentBoxes(vars.panelBoxes, "Conversation", ".Panel");

    injectGlobal({
        ".Section-Conversation .Panel": {
            "& .Button + .Button": {
                // fix excesive margins on the New message + Leave conversation buttons.
                marginTop: 0,
            },
        },
        ".Section-Conversation .ConversationMessage": {
            display: "flex",
            padding: 0,

            "& .PhotoWrap": {
                position: "static",
                marginRight: 16,
            },

            "& .Message": {
                marginTop: 4,
            },

            "& .ConversationMessage-content": {
                flex: 1,
                maxWidth: "100%",
            },
        },
    });
};
