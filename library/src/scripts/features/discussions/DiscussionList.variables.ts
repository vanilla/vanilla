/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { Variables } from "@library/styles/Variables";
import { listItemVariables } from "@library/lists/ListItem.variables";
import { IThemeVariables } from "@library/theming/themeReducer";
import { TagPreset } from "@library/metas/Tags.variables";
import { css } from "@emotion/css";
import { DeepPartial } from "redux";
import { IFont } from "@library/styles/cssUtilsTypes";

export interface IDiscussionItemOptions {
    excerpt?: {
        display?: boolean;
    };
    featuredImage?: {
        display?: boolean;
        fallbackImage?: string;
    };
    metas?: {
        asIcons?: boolean;
        display?: {
            category?: boolean;
            startedByUser?: boolean;
            lastUser?: boolean;
            viewCount?: boolean;
            lastCommentDate?: boolean;
            commentCount?: boolean;
            score?: boolean;
            userTags?: boolean;
            unreadCount?: boolean;
        };
    };
}

export const discussionListVariables = useThemeCache(
    (itemOptionsOverrides?: DeepPartial<IDiscussionItemOptions>, forcedVars?: IThemeVariables) => {
        /**
         * @varGroup discussionList
         * @description Variables affecting discussion lists
         */
        const makeThemeVars = variableFactory("discussionList", forcedVars);
        const listItemVars = listItemVariables(undefined, forcedVars);
        const globalVars = globalVariables(forcedVars);

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
        const contentBoxes = makeThemeVars("contentBoxes", Variables.contentBoxes(globalVars.contentBoxes));

        const panelBoxes = makeThemeVars("panelBoxes", Variables.contentBoxes(globalVars.panelBoxes));

        /**
         * @var discussionList.labels.tagPreset
         * @enum standard | primary | greyscale | colored
         */
        const labels = makeThemeVars("labels", {
            tagPreset: TagPreset.GREYSCALE,
        });

        /**
         * @var discussionList.userTags.tagPreset
         * @enum standard | primary | greyscale | colored
         */
        const userTags = makeThemeVars("userTags", {
            maxNumber: 3,
            tagPreset: TagPreset.STANDARD,
        });

        /**
         * @varGroup discussionList.item
         * @description A single discussion item.
         */
        const item = makeThemeVars(
            "item",
            {
                /**
                 * @var discussionList.item.options.iconPosition
                 * @description Choose where the icon of the list item is placed.
                 * @type string
                 * @enum default | meta | hidden
                 */
                options: {
                    iconPosition: listItemVars.options.iconPosition,
                },
                excerpt: {
                    /**
                     * @var discussionList.item.excerpt.display
                     * @type boolean
                     * @description Whether or not the excerpt in a discussion should display.
                     */
                    display: true,
                },
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
                        weight: globalVars.fonts.weights.normal,
                    }),
                    /**
                     * @varGroup discussionList.item.title.font
                     * @description Font variables title when it is being interacted with. (hover, active, focus).
                     */
                    fontState: Variables.font(listItemVars.title.fontState),
                },
                /**
                 * @varGroup discussionList.item.featuredImage
                 * @description Variables to define the display of a featured image on a discussion list item
                 */
                featuredImage: {
                    /**
                     * @var discussionList.item.featuredImage.display
                     * @type boolean
                     * @description Enable or disable the featured image
                     */
                    display: itemOptionsOverrides?.featuredImage?.display ?? false,
                    /**
                     * @var discussionList.item.featuredImage.fallbackImage
                     * @type string
                     * @format url
                     * @description The image to display when one is not included in the discussion item
                     */
                    fallbackImage: itemOptionsOverrides?.featuredImage?.fallbackImage ?? undefined,
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
            },
            itemOptionsOverrides,
        );

        return {
            profilePhoto,
            panelBoxes,
            contentBoxes,
            item,
            labels,
            userTags,
        };
    },
);
