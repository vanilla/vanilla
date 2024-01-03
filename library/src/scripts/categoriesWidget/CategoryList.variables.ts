/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { listItemVariables } from "@library/lists/ListItem.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { HomeWidgetItemContentType, homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { IThemeVariables } from "@library/theming/themeReducer";
import { Variables } from "@library/styles/Variables";
import { listVariables } from "@library/lists/List.variables";
import { ICategoryItemOptions } from "@library/categoriesWidget/CategoryItem";

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
        const homeWidgetItemVars = homeWidgetItemVariables({ imagePlacement: itemOptionsOverrides?.imagePlacement });

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
                 * @description Options for category item.
                 */
                options: {
                    icon: {
                        /**
                         * @var categoryList.item.options.icon.size
                         * @description Icon size.
                         * @type number
                         */

                        size: homeWidgetItemVars.icon.size,
                    },
                    /**
                     * @var categoryList.item.options.alignment
                     * @description Content alignment in category item (grid items only).
                     * @type string
                     */
                    alignment: homeWidgetItemVars.options.alignment,
                    /**
                     * @var categoryList.item.options.box
                     * @description Will allow to configure category list item box.
                     * @type string
                     */
                    box: Variables.box({
                        borderType: asTile
                            ? homeWidgetItemVars.options.box.borderType
                            : listVariables().options.itemBox.borderType,
                    }),
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
            },
            itemOptionsOverrides,
        );

        return { contentBoxes, panelBoxes, item };
    },
);
