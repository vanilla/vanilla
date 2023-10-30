/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { listItemVariables } from "@library/lists/ListItem.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    HomeWidgetItemContentType,
    IHomeWidgetItemOptions,
    homeWidgetItemVariables,
} from "@library/homeWidget/HomeWidgetItem.styles";
import { IThemeVariables } from "@library/theming/themeReducer";
import { Variables } from "@library/styles/Variables";

export interface ICategoryItemOptions {
    imagePlacement?: IHomeWidgetItemOptions["imagePlacement"];
    contentType?: HomeWidgetItemContentType;
    description?: {
        display?: boolean;
    };
    followButton?: {
        display?: boolean;
    };
    metas?: {
        asIcons?: boolean | string;
        includeSubcategoriesCount?: string[]; // will add subcategories counts to existing category counts
        display?: {
            discussionCount?: boolean;
            commentCount?: boolean;
            postCount?: boolean;
            followerCount?: boolean;
            lastPostName?: boolean;
            lastPostAuthor?: boolean;
            lastPostDate?: boolean;
            subcategories?: boolean;
        };
    };
}

/**
 * Variables affecting category list.
 */
export const categoryListVariables = useThemeCache(
    (itemOptionsOverrides?: ICategoryItemOptions, asTile = false, forcedVars?: IThemeVariables) => {
        /**
         * @varGroup categoryList
         * @description Variables affecting category lists
         */
        const makeVars = variableFactory("categoryList", forcedVars);
        const listItemVars = listItemVariables(undefined, forcedVars);
        const homeWidgetItemVars = homeWidgetItemVariables();

        /**
         * @varGroup categoryList.contentBoxes
         * @description Content boxes for the category list page.
         * @expand contentBoxes
         */
        const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

        const panelBoxes = makeVars("panelBoxes", Variables.contentBoxes(globalVariables().panelBoxes));

        /**
         * @varGroup categoryList.item
         * @description A single category item.
         */
        const item = makeVars(
            "item",
            {
                /**
                 * @varGroup categoryList.item.options
                 * @description Options for icon and alignment styling.
                 */
                options: {
                    icon: {
                        /**
                         * @var categoryList.item.options.icon.size
                         * @description Icon size.
                         * @type number
                         */
                        size: itemOptionsOverrides?.imagePlacement === "left" ? 48 : 72,
                    },
                    /**
                     * @var categoryList.item.options.alignment
                     * @description Content alignment in category item (grid items only).
                     * @type number
                     */
                    alignment:
                        itemOptionsOverrides?.contentType === HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE
                            ? "left"
                            : "center",
                },
                /**
                 * @varGroup categoryList.item.heading
                 * @description Items as heading styling.
                 */
                heading: {
                    firstLevel: {
                        /**
                         * @var categoryList.item.heading.firstLevel.font
                         * @description Font variables for the default state of the first level heading.
                         */
                        font: Variables.font({}),
                    },
                    secondLevel: {
                        /**
                         * @var categoryList.item.heading.secondLevel.font
                         * @description Font variables for the default state of the scond level heading.
                         */
                        font: Variables.font({}),
                    },
                },
                title: {
                    /**
                     * @var categoryList.item.title.font
                     * @description Font variables for the default state of the title.
                     */
                    font: Variables.font(
                        asTile
                            ? {
                                  ...homeWidgetItemVars.name.font,
                                  color:
                                      itemOptionsOverrides?.contentType === HomeWidgetItemContentType.TITLE_BACKGROUND
                                          ? homeWidgetItemVars.background.fg.color
                                          : homeWidgetItemVars.name.font.color,
                              }
                            : listItemVars.title.font,
                    ),
                    /**
                     * @var categoryList.item.title.fontState
                     * @description Font state variables for the title.
                     */
                    fontState: Variables.font(asTile ? homeWidgetItemVars.name.fontState : {}),
                },
                description: {
                    /**
                     * @var categoryList.item.description.display
                     * @type boolean
                     * @description Whether or not the description in a category should display.
                     */
                    display: true,
                    /**
                     * @var categoryList.item.description.font
                     * @description Font variables for the default state of the description.
                     */
                    font: Variables.font({}),
                },
                followButton: {
                    /**
                     * @var categoryList.item.followButton.display
                     * @type boolean
                     * @description Whether or not the follow button in a category should display.
                     */
                    display: true,
                },
                /**
                 * @varGroup categoryList.item.metas
                 * @description Metadata displayed on each item in the category list.
                 */
                metas: {
                    /**
                     * @var categoryList.item.metas.asIcons
                     * @description When enabled, certain metadata such as (view counts, comment counts) are labeled with icons instead of text.
                     * @type boolean
                     */
                    asIcons: false,
                    /**
                     * @varGroup categoryList.item.metas.display
                     * @description Controls which attributes are displayed in the metadata row.
                     * @type boolean
                     */
                    display: {
                        /**
                         * @var categoryList.item.metas.display.discussionCount
                         * @description Display discussion count in metas.
                         * @type boolean
                         */
                        discussionCount: true,
                        /**
                         * @var categoryList.item.metas.display.commentCount
                         * @description Display comment count in metas.
                         * @type boolean
                         */
                        commentCount: true,
                        /**
                         * @var categoryList.item.metas.display.postCount
                         * @description Display post count in metas.
                         * @type boolean
                         */
                        postCount: true,
                        /**
                         * @var categoryList.item.metas.display.followerCount
                         * @description Display follower count in metas.
                         * @type boolean
                         */
                        followerCount: true,
                        /**
                         * @var categoryList.item.metas.display.lastPostName
                         * @description Display last post name in metas.
                         * @type boolean
                         */
                        lastPostName: true,
                        /**
                         * @var categoryList.item.metas.display.lastPostAuthor
                         * @description Display last post author in metas.
                         * @type boolean
                         */
                        lastPostAuthor: true,
                        /**
                         * @var categoryList.item.metas.display.lastPostDate
                         * @description Display last post date in metas.
                         * @type boolean
                         */
                        lastPostDate: true,
                        /**
                         * @var categoryList.item.metas.display.subcategories
                         * @description Display subcategories in metas.
                         * @type boolean
                         */
                        subcategories: true,
                    },
                },
            },
            itemOptionsOverrides,
        );

        return { contentBoxes, panelBoxes, item };
    },
);
