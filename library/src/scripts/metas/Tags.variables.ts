/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getPixelNumber, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Variables } from "@library/styles/Variables";
import { styleUnit } from "@library/styles/styleUnit";
import { ColorHelper } from "csx";
import { forceInt } from "@vanilla/utils";
import { ensureColorHelper } from "@library/styles/styleHelpersColors";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { LocalVariableMapping } from "@library/styles/VariableMapping";
import { metasVariables } from "@library/metas/Metas.variables";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { BorderType } from "@library/styles/styleHelpersBorders";

export const tagsVariables = useThemeCache(() => {
    /**
     * @varGroup tags
     * @description Variables affecting tags globally
     */
    const makeThemeVars = variableFactory("tags", undefined, [
        new LocalVariableMapping({
            "border.radius": "tagItem.border.radius",
        }),
    ]);

    const globalVars = globalVariables();
    const { font: metasFont } = metasVariables();

    /**
     * @varGroup tags.font
     * @description Tag font
     * @expand font
     */
    const font = makeThemeVars("font", Variables.font(metasFont));

    /**
     * @varGroup tags.fontState
     * @description Tag font when hovered, active, or focused
     * @expand font
     */
    const fontState = makeThemeVars(
        "fontState",
        Variables.font({
            color: globalVars.mainColors.statePrimary,
        }),
    );

    /**
     * @varGroup tags.background
     * @description Tag background
     * @expand background
     */
    const background = makeThemeVars(
        "background",
        Variables.background({
            color: globalVars.mainColors.bg,
        }),
    );

    /**
     * @varGroup tags.padding
     * @description Spacing around the tags' text
     * @expand spacing
     */
    const padding = makeThemeVars(
        "padding",
        Variables.spacing({
            vertical: 0,
            horizontal: 9,
        }),
    );

    /**
     * @varGroup tags.border
     * @description Tag default border
     * @expand border
     */
    let border = makeThemeVars(
        "border",
        Variables.border({
            color: metasFont.color,
            width: 1, // these are really small, I don't think it makes sense to default to anything else.
        }),
    );

    border = makeThemeVars(
        "border",
        Variables.border({
            ...border,
            // If border radius not overwritten, calculate it to be round.
            radius:
                border.radius ??
                ((((font.lineHeight || 1.45) as number) * ((font.size as number) ?? 12)) as number) / 2 +
                    ((padding.vertical || 0) as number) +
                    (!!border.width && border.width > 0 ? (border.width as number) : 0),
        }),
    );

    const height =
        getPixelNumber(font.size) * forceInt(font.lineHeight, 1) +
        getPixelNumber(padding.vertical) * 2 +
        getPixelNumber(border.width) * 2;

    return {
        font,
        fontState,
        padding,
        border,
        background,
        height,
    };
});

interface ITagSimple {
    fontColor?: ColorHelper | string;
    bgColor?: ColorHelper | string;
    borderColor?: ColorHelper | string;
}
interface ITag extends ITagSimple {
    fontColorState?: ITagSimple["fontColor"];
    bgColorState?: ITagSimple["fontColor"];
    borderColorState?: ITagSimple["borderColor"];
}

export type TagType = ITag;

export enum TagPreset {
    STANDARD = "standard",
    PRIMARY = "primary",
    GREYSCALE = "greyscale",
    COLORED = "colored",
}

export const tagPresetVariables = useThemeCache(function (): { [key in TagPreset]: TagType } {
    const makeThemeVars = variableFactory("tags", undefined, [
        new LocalVariableMapping({
            "standard.fontColor": "tagItem.font.color",
            "standard.fontColorState": "tagItem.fontState.color",
            "standard.bgColor": "tagItem.background.color",
            "standard.bgColorState": "tagItem.backgroundState.color",
            "standard.borderColor": "tagItem.border.color",
            "standard.borderColorState": "tagItem.borderState.color",
        }),
    ]);
    const { font, fontState, background } = tagsVariables();
    const globalVars = globalVariables();

    /**
     * @varGroup tags.primary
     * @title Primary tag preset
     * @expand tagPreset
     */

    let primary: TagType = makeThemeVars("primary", {
        fontColor: globalVars.mainColors.primary,
        bgColor: background.color,
        borderColor: "#dddee0",
    });

    primary = makeThemeVars("primary", {
        ...primary,
        fontColorState: fontState.color,
        bgColorState: primary.bgColor,
        borderColorState: fontState.color,
    });

    /**
     * @varGroup tags.standard
     * @title Standard tag preset
     * @expand tagPreset
     */
    let standard: TagType = makeThemeVars("standard", {
        fontColor: font.color!,
        bgColor: background.color,
        fontColorState: fontState.color,
    });

    standard = makeThemeVars("standard", {
        ...standard,
        borderColor: primary.borderColor,
        fontColorState: primary.fontColorState,
        borderColorState: primary.borderColorState,
        bgColorState: primary.bgColorState,
    });

    /**
     * @varGroup tags.greyscale
     * @title Greyscale tag preset
     * @expand tagPreset
     */

    let greyscale: TagType = makeThemeVars("greyscale", {
        fontColor: "#555a62",
        bgColor: "#EEEEEF",
    });

    greyscale = makeThemeVars("greyscale", {
        ...greyscale,
        fontColorState: greyscale.fontColor,
        borderColor: greyscale.bgColor,
        bgColorState: "#D8D8D8",
    });

    greyscale = makeThemeVars("greyscale", {
        ...greyscale,
        borderColorState: greyscale.bgColorState,
    });

    /**
     * @varGroup tags.colored
     * @title Colored tag preset
     * @expand tagPreset
     */

    let colored: TagType = makeThemeVars("colored", {
        fontColor: globalVars.mainColors.primary,
    });

    const coloredBaseColor = ensureColorHelper(colored.fontColor!);
    const isLight = ColorsUtils.isLightColor(coloredBaseColor);

    colored = makeThemeVars("colored", {
        ...colored,
        bgColor: isLight ? coloredBaseColor.darken(0.6) : coloredBaseColor.fade(0.08),
        borderColor: isLight ? coloredBaseColor.lighten(0.2) : coloredBaseColor.fade(0.6),
    });

    colored = makeThemeVars("colored", {
        ...colored,
        fontColorState: colored.fontColor,
        bgColorState: colored.bgColor,
        borderColorState: coloredBaseColor,
    });

    return {
        primary,
        standard,
        greyscale,
        colored,
    };
});

export enum TagListStyle {
    LIST = "list",
    CLOUD = "cloud",
}

export const tagCloudVariables = useThemeCache((options?: IHomeWidgetContainerOptions) => {
    const makeThemeVars = variableFactory("tags", options, [
        new LocalVariableMapping({
            tagCloud: "tagItem",
        }),
    ]);
    const globalVars = globalVariables();

    /**
     * @varGroup tags.tagCloud
     * @description Tag items are found in the tag cloud module.
     */
    const tagCloud = makeThemeVars("tagCloud", {
        /**
         * @var tags.tagCloud.type
         * @description Set the display type of tag items
         * @type string
         * @enum cloud | list
         */
        type: TagListStyle.CLOUD,

        /**
         * @varGroup tags.tagCloud.box
         * @commonTitle tagCloud - box
         * @expand box
         */
        box: Variables.box({
            background: options?.innerBackground,
            borderType: options?.borderType as BorderType,
            border: globalVars.border,
        }),

        /**
         * @var tags.tagCloud.tagPreset
         * @enum standard | primary | greyscale | colored
         */
        tagPreset: TagPreset.STANDARD,

        /**
         * @varGroup tags.tagCloud.margin
         * @description Margins between tag cloud items
         * @expand spacing
         */
        margin: Variables.spacing({
            horizontal: styleUnit(2),
            vertical: styleUnit(2),
        }),

        /**
         * @var tags.tagCloud.showCount
         * @title Tag Item Show Count
         * @description Option to display or hide counts in the tag cloud.
         * @type boolean
         */
        showCount: true,
    });

    return tagCloud;
});
