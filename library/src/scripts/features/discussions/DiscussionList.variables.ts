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
     * @varGroup discussionList.userTags
     * @description If userTags shown/hide and number to show on discussion, hodden by default.
     */
    const userTags = makeThemeVars("userTags", {
        maxNumber: 3,
    });

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

        /**
         * @varGroup discussionList.item.metas
         * @description Metadata displayed on each item in the discussion list.
         */
        metas: {
            /**
             * @var discussionList.item.metas.asIcons
             * @description When enabled, certain metadata such as (view counts, comment counts) are labeled with icons instead of text.
             * @type boolean
             */
            asIcons: false,

            /**
             * @varGroup discussionList.item.metas.display
             * @description Controls which attributes are displayed in the metadata row.
             * @type boolean
             */
            display: {
                /**
                 * @var discussionList.item.metas.display.category
                 * @description Display a link to the discussion's category.
                 * @type boolean
                 */
                category: true,
                /**
                 * @var discussionList.item.metas.display.startedByUser
                 * @description Display a link to  user who started the discussion.
                 * @type boolean
                 */
                startedByUser: true,
                /**
                 * @var discussionList.item.metas.display.lastUser
                 * @description Display a link to the last user to participate in the discussion.
                 * @type boolean
                 */
                lastUser: true,
                /**
                 * @var discussionList.item.metas.display.unreadCount
                 * @description Highlight the number of unread comments, if any.
                 * @type boolean
                 */
                unreadCount: true,
                /**
                 * @var discussionList.item.metas.display.qnaStatus
                 * @description Display the Q&A status. if applicable.
                 * @type boolean
                 */
                qnaStatus: true,
                /**
                 * @var discussionList.item.metas.display.viewCount
                 * @description Display the view count.
                 * @type boolean
                 */
                viewCount: true,
                /**
                 * @var discussionList.item.metas.display.commentCount
                 * @description Display the comment count.
                 * @type boolean
                 */
                commentCount: true,
                /**
                 * @var discussionList.item.metas.display.lastCommentDate
                 * @description Display the date of the last comment.
                 * @type boolean
                 */
                lastCommentDate: true,
                /**
                 * @var discussionList.item.metas.display.score
                 * @description Display the reactions score, if applicable.
                 * @type boolean
                 */
                score: true,
                /**
                 * @var discussionList.item.metas.display.userTags
                 * @description Display the reactions score, if applicable.
                 * @type boolean
                 */
                userTags: true,
                /**
                 * @var discussionList.item.metas.display.resolved
                 * @description Display the resolved icon, if applicable.
                 * @type boolean
                 */
                resolved: true,
            },
        },
    });

    return {
        profilePhoto,
        panelBoxes,
        contentBoxes,
        item,
        userTags,
    };
});
