/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { styleUnit } from "@library/styles/styleUnit";

export enum TagType {
    LIST = "list",
    CLOUD = "cloud",
}

export const tagVariables = useThemeCache(() => {
    /**
     * @varGroup tags
     * @description Variables affecting tags throughout the application.
     */
    const makeThemeVars = variableFactory("tags");
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const colorOverwrite = makeThemeVars("colorOverwrite", {
        ...Variables.clickable({
            default: globalVars.mainColors.primary,
            allStates: globalVars.mainColors.statePrimary,
        }),
    });

    const linkColors = Mixins.clickable.itemState(colorOverwrite, { disableTextDecoration: true });

    const nested = makeThemeVars("states", linkColors);

    const colors = makeThemeVars("color", {
        fg: metasVars.font.color,
    });

    /**
     * @varGroup tags.tagItem
     * @description Tag items are found in the tag cloud module.
     */
    const tagItem = makeThemeVars("tagItem", {
        /**
         * @var forumFonts.tagItem.type
         * @description Set the display type of tag items
         * @type string
         * @enum cloud | list
         */
        type: TagType.CLOUD,
        /**
         * @varGroup tags.tagItem.font
         * @description Font variables for the default state of the tag label
         */
        font: Variables.font({}),
        /**
         * @varGroup tags.tagItem.fontState
         * @description Font variables title when a tag item is being interacted with. (hover, active, focus).
         */
        fontState: Variables.font({}),
        /**
         * @varGroup tags.tagItem.background
         * @description Background variables for the default state of the tag background
         */
        background: Variables.background({}),
        /**
         * @varGroup tags.tagItem.backgroundState
         * @description Background variables title when a tag item is being interacted with. (hover, active, focus)
         */
        backgroundState: Variables.background({}),
        /**
         * @varGroup tags.tagItem.margin
         * @description Margins between tag items
         * @expand spacing
         */
        margin: Variables.spacing({
            right: styleUnit(4),
            vertical: styleUnit(2),
        }),
        /**
         * @varGroup tags.tagItem.border
         * @description Borders of tag items
         * @expand border
         */
        border: Variables.border({
            radius: styleUnit(12),
        }),
        /**
         * @varGroup tags.tagItem.borderState
         * @description Border variables title when a tag item is being interacted with. (hover, active, focus)
         * @expand border
         */
        borderState: Variables.border({
            radius: styleUnit(12),
        }),

        /**
         * @var tags.tagItem.showCount
         * @title Tag Item Show Count
         * @description Option to display or hide counts in the tag cloud.
         * @type boolean
         */
        showCount: true,
    });

    const font = makeThemeVars(
        "font",
        Variables.font({
            color: colors.fg,
            lineHeight: metasVars.font.lineHeight,
            size: metasVars.font.size,
        }),
    );

    /**
     * @varGroup tags.padding
     * @description To control the padding  of the tags
     * @expand spacing
     */
    const padding = makeThemeVars(
        "padding",
        Variables.spacing({
            vertical: 0,
            horizontal: 9,
        }),
    );

    const margin = makeThemeVars("margin", Variables.spacing(metasVars.spacing));

    const border = makeThemeVars(
        "border",
        Variables.border({
            color: colors.fg,
            width: 1, // these are really small, I don't think it makes sense to default to anything else.
        }),
    );

    const background = makeThemeVars("background", Variables.background({}));

    // If border radius not overwritten, calculate it to be round.
    if (!border.radius) {
        border.radius =
            ((((font.lineHeight || 1.45) as number) * ((font.size as number) ?? 12)) as number) / 2 +
            ((padding.vertical || 0) as number) +
            (!!border.width && border.width > 0 ? (border.width as number) : 0);
    }

    const output = {
        colors,
        font,
        padding,
        border,
        margin,
        tagItem,
        background,
        nested,
    };

    return output;
});

// For now we only have compatibility styles in //forumTagStyles.ts
