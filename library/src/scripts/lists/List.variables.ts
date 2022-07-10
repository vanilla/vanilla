/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ListItemLayout } from "@library/lists/ListItem.variables";
import { IPartialBoxOptions } from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpers";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export interface IListOptions {
    box: IPartialBoxOptions;
    itemBox: IPartialBoxOptions;
    itemLayout: ListItemLayout;
}

/**
 * @varGroup list
 * @description Global default styles for lists.
 */
export const listVariables = useThemeCache((componentOptions?: Partial<IListOptions>) => {
    const makeVars = variableFactory("list");
    const options = makeVars(
        "options",
        {
            /**
             * @varGroup list.options.box
             * @description Default box style for all lists.
             * @expand box
             */
            box: Variables.box({
                borderType: BorderType.NONE,
            }),
            /**
             * @varGroup list.options.itemBox
             * @description Default box style for all lists items.
             * @expand box
             */
            itemBox: Variables.box({
                borderType: BorderType.SEPARATOR,
            }),
            /**
             * @var list.options.itemLayout
             * @description Default layout for all lists items.
             * @type string
             * @enum title-description-metas | title-metas-description | title-metas
             */
            itemLayout: ListItemLayout.TITLE_DESCRIPTION_METAS,
        },
        componentOptions,
    );

    return { options };
});
