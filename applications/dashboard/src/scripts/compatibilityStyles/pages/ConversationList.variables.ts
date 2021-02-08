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
 * @varGroup conversationList
 * @description Variables affecting lists of conversations. This is often referred to as the Inbox.
 */
export const conversationListVariables = useThemeCache(() => {
    const makeVars = variableFactory("conversationList");

    /**
     * @varGroup conversationList.contentBoxes
     * @description Content boxes for the conversations list page (or inbox).
     * @expand contentBoxes
     */
    const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    return { contentBoxes };
});
