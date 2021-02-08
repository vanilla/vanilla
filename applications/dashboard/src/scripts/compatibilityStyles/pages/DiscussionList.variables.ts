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
 * @varGroup discussionList
 * @description Variables affecting lists of discussions. Notably this is normally what you see when viewing a category.
 */
export const discussionListVariables = useThemeCache(() => {
    const makeVars = variableFactory("discussionList");

    /**
     * @varGroup discussionList.contentBoxes
     * @description Content boxes for the discussion list page.
     * @expand contentBoxes
     */
    const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    /**
     * @varGroup discussionList.item
     * @description A single discussion item.
     */
    const item = makeVars("item", {
        /**
         * @varGroup discussionList.item.title
         * @description The title of a single discussion item.
         */
        title: {
            /**
             * @varGroup discussionList.item.title.font
             * @description Font variables for the default state of the title.
             */
            font: Variables.font({}),
            /**
             * @varGroup discussionList.item.title.font
             * @description Font variables for the "read" state of the title. (When the discussion has already been read).
             */
            fontRead: Variables.font({}),
            /**
             * @varGroup discussionList.item.title.font
             * @description Font variables title when it is being interacted with. (hover, active, focus).
             */
            fontState: Variables.font({}),
        },
    });

    return { contentBoxes, item };
});
