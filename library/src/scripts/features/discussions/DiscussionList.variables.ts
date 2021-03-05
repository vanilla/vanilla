/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { Variables } from "@library/styles/Variables";
import { listItemVariables } from "@library/lists/ListItem.variables";

export const discussionListVariables = useThemeCache(() => {
    /**
     * @varGroup discussionList
     * @description Variables affecting discussion lists
     */
    const makeThemeVars = variableFactory("discussionList");
    const listItemVars = listItemVariables();

    /**
     * @varGroup discussionList.profilePhoto
     * @description Variables for the profile photo
     */
    const profilePhoto = makeThemeVars("profilePhoto", {
        size: UserPhotoSize.MEDIUM,
    });

    /**
     * @varGroup discussionList.contentBoxes
     * @description Content boxes for the discussion list page.
     * @expand contentBoxes
     */
    const contentBoxes = makeThemeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    const panelBoxes = makeThemeVars("panelBoxes", Variables.contentBoxes(globalVariables().panelBoxes));

    /**
     * @varGroup discussionList.item
     * @description A single discussion item.
     */
    const item = makeThemeVars("item", {
        title: {
            /**
             * @varGroup discussionList.item.title.font
             * @description Font variables for the default state of the title.
             */
            font: Variables.font(listItemVars.title.font),
            /**
             * @varGroup discussionList.item.title.font
             * @description Font variables for the "read" state of the title. (When the discussion has already been read).
             */
            fontRead: Variables.font({
                weight: globalVariables().fonts.weights.normal,
            }),
            /**
             * @varGroup discussionList.item.title.font
             * @description Font variables title when it is being interacted with. (hover, active, focus).
             */
            fontState: Variables.font(listItemVars.title.fontState),
        },
    });

    return {
        profilePhoto,
        panelBoxes,
        contentBoxes,
        item,
    };
});
