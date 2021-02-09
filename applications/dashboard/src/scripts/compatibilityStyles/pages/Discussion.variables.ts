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
 * @varGroup discussion
 * @description Variables affecting a discussion page.
 * This is generally a single discussion and the comments responding to it.
 */
export const discussionVariables = useThemeCache(() => {
    const makeVars = variableFactory("discussion");

    /**
     * @varGroup discussion.contentBoxes
     * @description Content boxes for the discussion page (or comment list).
     * @expand contentBoxes
     */
    const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    return { contentBoxes };
});
