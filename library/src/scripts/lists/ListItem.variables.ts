/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export enum ListItemIconPosition {
    /**
     * Icon appears on the left side of the content
     */
    DEFAULT = "default",

    /**
     * Icon appears on the left side of the meta content
     */
    META = "meta",

    /**
     * Icon is hidden
     */
    HIDDEN = "hidden",
}
export interface IListItemOptions {
    layout: ListItemLayout;
}

export interface IListItemComponentOptions {
    iconPosition: ListItemIconPosition;
}

export enum ListItemLayout {
    TITLE_DESCRIPTION_METAS = "title-description-metas",
    TITLE_METAS_DESCRIPTION = "title-metas-description",
    TITLE_METAS = "title-metas",
}

export const listItemVariables = useThemeCache((componentOptions?: Partial<IListItemComponentOptions>) => {
    const makeVars = variableFactory("listItem");
    const globalVars = globalVariables();

    const options = makeVars(
        "options",
        {
            iconPosition: ListItemIconPosition.DEFAULT,
        },
        componentOptions,
    );

    /**
     * @varGroup listItem.title
     * @description The title of a single discussion item.
     */
    const titleInit = makeVars("title", {
        /**
         * @varGroup listItem.title.font
         * @description Font variables for the default state of the title.
         */
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("large", "semiBold"),
            color: globalVars.mainColors.fg,
        }),
    });

    const isDefaultFontColor = titleInit.font.color === globalVars.mainColors.fg;

    const title = makeVars("title", {
        ...titleInit,
        /**
         * @varGroup listItem.title.font
         * @description Font variables title when it is being interacted with. (hover, active, focus).
         */
        fontState: Variables.font({
            color: isDefaultFontColor
                ? globalVars.mainColors.primary
                : ColorsUtils.offsetLightness(titleInit.font.color!, 0.04),
        }),
    });

    return {
        title,
        options,
    };
});
