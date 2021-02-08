/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

/**
 * @varGroup conversation
 * @description Variables affecting a single convesation made up of messages between 2 users.
 */
export const conversationVariables = useThemeCache(() => {
    const makeVars = variableFactory("conversation");

    /**
     * @varGroup conversation.contentBoxes
     * @description Content boxes for the conversation page (or messages page).
     * @expand contentBoxes
     */
    const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    return { contentBoxes };
});
