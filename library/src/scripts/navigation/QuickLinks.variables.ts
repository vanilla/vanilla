/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ListSeparation } from "@library/styles/cssUtilsTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { LocalVariableMapping } from "@library/styles/VariableMapping";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";

/**
 * @varGroup quickLinks
 * @description Quick links are a component of customizable links, normally appearing in a side panel.
 */
export const quickLinksVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("quickLinks", forcedVars, [
        new LocalVariableMapping({
            "listItem.font.color": "listItem.fgColor.default",
            "listItem.fontState.color": "listItem.fgColor.allStates",
        }),
    ]);
    const globalVars = globalVariables(forcedVars);

    /**
     * @varGroup quickLinks.box
     * @title Quick Links - Box
     * @expand box
     */
    const box = makeThemeVars("box", Variables.box(globalVars.panelBoxes.depth2));

    const links: INavigationVariableItem[] = makeThemeVars("links", []);
    const counts: Record<string, number | null> = makeThemeVars("counts", {});

    /**
     * @varGroup quickLinks.listItem
     */
    const listInit = makeThemeVars("listItem", {
        /**
         * @var quickLinks.listItem.listSeparation
         * @description Describe how the list items should be separated from each other.
         * - none - The list items only have whitespace between them.
         * - border - The list items each have a full border and whitespace between the borders.
         * - separator - There is whitespace and a single line separating 2 items.
         * @type string
         * @enum none | border | separator
         */
        listSeparation: ListSeparation.NONE,
        /**
         * @var quickLinks.listItem.listSeparationColor
         * @description Color of the separator order border.
         * @type string
         * @format color
         */
        listSeparationColor: globalVars.border.color,
        /**
         * @var quickLinks.listItem.listSeparationWidth
         * @description The width/size of the separator or border.
         * @type number
         */
        listSeparationWidth: globalVars.border.width,

        /**
         * @varGroup quickLinks.listItem.font
         * @Title Font
         * @commonDescription The font applied to the list items
         * @expand font
         */
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("medium", "normal"),
            color: globalVars.mainColors.fg,
            textDecoration: "auto",
        }),
    });

    const isBorderSep = listInit.listSeparation === ListSeparation.BORDER;
    const isLineSep = listInit.listSeparation === ListSeparation.SEPARATOR;
    const listItem = makeThemeVars("listItem", {
        ...listInit,

        /**
         * @varGroup quickLinks.listItem.fontState
         * @Title Font (state)
         * @commonDescription The font applied to the links when hovered, focused, or active
         * @expand font
         */
        fontState: Variables.font({
            color:
                listInit.font.color === globalVars.mainColors.fg
                    ? globalVars.mainColors.stateSecondary
                    : ColorsUtils.offsetLightness(listInit.font.color!, 0.05),
        }),

        /**
         * @varGroup quickLinks.listItem.padding
         * @title Padding
         * @commonDescription The difference between padding and spacing
         * will only be apparent when there is a border or background color.
         * @expand spacing
         */
        padding: Variables.spacing({
            vertical: isLineSep ? 12 : 6,
            horizontal: isBorderSep || isLineSep ? 12 : 0,
        }),
        /**
         * @varGroup quickLinks.listItem.spacing
         * @title Padding
         * @commonDescription The difference between padding and spacing
         * will only be apparent when there is a border or background color.
         * @expand spacing
         */
        spacing: Variables.spacing({
            vertical: isBorderSep ? 6 : 0,
        }),
    });

    /**
     * @varGroup quickLinks.count.font
     * @title QuickLinks - Count Font
     * @description Some quick links may have a count.
     * @expand font
     */
    const count = makeThemeVars("count", {
        font: {
            ...listItem.font,
            color: globalVars.mainColors.fg,
        },
    });

    return {
        listItem,
        count,
        box,
        links,
        counts,
    };
});
