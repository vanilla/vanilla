/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { Variables } from "@library/styles/Variables";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { em, px } from "csx";
import { IThemeVariables } from "@library/theming/themeReducer";

export enum TableStyle {
    HORIZONTAL_BORDER = "horizontalBorder",
    HORIZONTAL_BORDER_STRIPED = "horizontalBorderStriped",
    VERTICAL_BORDER = "verticalBorder",
    VERTICAL_BORDER_STRIPED = "verticalBorderStriped",
}

/**
 * @varGroup userContent
 * @commonTitle User Content
 * @description Variables affecting content created by users of the site.
 * If you input it through a text editor, it's likely user content.
 */
export const userContentVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("userContent", forcedVars);
    const globalVars = globalVariables(forcedVars);
    const { mainColors } = globalVars;

    /**
     * @varGroup userContent.fonts
     */
    const fonts = makeThemeVars("fonts", {
        /**
         * @var userContent.fonts.size
         * @description Default font size for user content.
         */
        size: globalVars.fonts.size.large,

        /**
         * @varGroup userContent.fonts.headings
         * @commonDescription These are best specified as a relative units. (Eg. "1.5em", "2em").
         */
        headings: {
            /**
             * @var userContent.fonts.headings.h1
             */
            h1: "2em",
            /**
             * @var userContent.fonts.headings.h2
             */
            h2: "1.5em",
            /**
             * @var userContent.fonts.headings.h3
             */
            h3: "1.25em",
            /**
             * @var userContent.fonts.headings.h4
             */
            h4: "1em",
            /**
             * @var userContent.fonts.headings.h5
             */
            h5: ".875em",
            /**
             * @var userContent.fonts.headings.h6
             */
            h6: ".85em",
        },
    });

    /**
     * @varGroup userContent.tables
     * @title User Content - Tables
     */
    const tableInit = makeThemeVars("tables", {
        /**
         * @var userContent.tables.style
         * @title UserContent - Tables - Style
         * @description Choose a preset for the table styles.
         * @type string
         * @enum horizontalBorder|horizontalBorderStriped|verticalBorder|verticalBorderStriped
         */
        style: TableStyle.VERTICAL_BORDER_STRIPED,
        /**
         * @varGroup userContent.tables.borders
         * @title UserContent - Tables - Border Styles
         * @expand border
         */
        borders: globalVars.border,

        /**
         * @var userContent.tables.mobileBreakpoint
         * @title UserContent - Tables - Mobile Breakpoint
         * @description The device width (pixels) where the table switches to a mobile layout.
         * @type number
         */
        mobileBreakpoint: 600,
    });

    /**
     * @varGroup userContent.tables
     * @title User Content - Tables
     */
    const tables = makeThemeVars("tables", {
        ...tableInit,
        /**
         * @var userContent.tables.striped
         * @title UserContent - Tables - Striped Rows
         * @description Display even numbered rows with a different background
         * @type boolean
         */
        striped: [TableStyle.HORIZONTAL_BORDER_STRIPED, TableStyle.VERTICAL_BORDER_STRIPED].includes(tableInit.style),
        /**
         * @var userContent.tables.stripeColor
         * @title UserContent - Tables - Stripe Row Color
         * @description Color for the striped rows
         * @type string
         * @format hex-color
         */
        stripeColor: globalVars.mixBgAndFg(0.05),
        /**
         * @var userContent.tables.outerBorderRadius
         * @title UserContent - Tables - Outer Border Radius
         * @description Set the radius of the table's outer border. Numerical units are interpreted as pixels.
         * @type string | number
         */
        outerBorderRadius: [TableStyle.VERTICAL_BORDER_STRIPED, TableStyle.VERTICAL_BORDER].includes(tableInit.style)
            ? 4
            : 0,
        /**
         * @varGroup userContent.tables.horizontalBorders
         * @title UserContent - Tables - Horizontal Borders
         */
        horizontalBorders: {
            /**
             * @var userContent.tables.horizontalBorders.enabled
             * @title UserContent - Tables - Horizontal Borders - Enabled
             * @description Display horizontal table borders
             * @type boolean
             */
            enabled: true, // All current variants have horizontal borders.
            /**
             * @varGroup userContent.tables.horizontalBorders.borders
             * @title UserContent - Tables - Horizontal Borders - Border Styles
             * @expand border
             */
            borders: tableInit.borders,
        },
        /**
         * @varGroup userContent.tables.verticalBorders
         * @title UserContent - Tables - Vertical Borders
         */
        verticalBorders: {
            /**
             * @var userContent.tables.verticalBorders.enabled
             * @title UserContent - Tables - Vertical Borders - Enabled
             * @description Display vertical table borders
             * @type boolean
             */
            enabled: [TableStyle.VERTICAL_BORDER_STRIPED, TableStyle.VERTICAL_BORDER].includes(tableInit.style),
            /**
             * @varGroup userContent.tables.verticalBorders.borders
             * @title UserContent - Tables - Vertical Borders - Border Styles
             * @expand border
             */
            borders: tableInit.borders,
        },
    });

    const blocks = makeThemeVars("blocks", {
        margin: globalVars.spacer.componentInner,
        fg: mainColors.fg,
        bg: globalVars.mixBgAndFg(0.05),
    });

    /**
     * @varGroup userContent.embeds
     * @title User Content - Embeds
     */
    const embeds = makeThemeVars("embeds", {
        /**
         * @var userContent.embeds.bg
         * @title Background
         * @type string
         * @format hex-color
         */
        bg: mainColors.bg,
        /**
         * @var userContent.embeds.fg
         * @title Text Color
         * @type string
         * @format hex-color
         */
        fg: mainColors.fg,

        /**
         * @var userContent.embeds.borderRadius
         * @title Border Radius
         * @description Border radius of an embed in pixels.
         * @type string|number
         */
        borderRadius: px(4),
    });

    /**
     * @varGroup userContent.code
     * @title User Content - Code
     * @commonDescription Applies to inline and block style code items.
     */
    const code = makeThemeVars("code", {
        /**
         * @var userContent.code.fontSize
         * @type string|number
         */
        fontSize: em(0.85),
        /**
         * @var userContent.code.borderRadius
         * @type string|number
         */
        borderRadius: 2,
    });

    /**
     * @varGroup userContent.codeInline
     * @title User Content - Code (Inline)
     * @commonDescription Applies only to inline code elements. Not Blocks.
     */
    const codeInline = makeThemeVars("codeInline", {
        /**
         * @var userContent.codeInline.borderRadius
         * @type string|number
         */
        borderRadius: code.borderRadius,

        padding: Variables.spacing({
            vertical: "0.2em",
            horizontal: "0.4em",
        }),

        /**
         * @var userContent.codeInline.fg
         * @title Text Color
         * @type string
         * @format hex-color
         */
        fg: blocks.fg,
        /**
         * @var userContent.codeInline.bg
         * @title Background
         * @type string
         * @format hex-color
         */
        bg: blocks.bg,
    });

    /**
     * @varGroup userContent.codeBlock
     * @title User Content - Code (Block)
     * @commonDescription Applies only to code block elements. Not inline code.
     */
    const codeBlock = makeThemeVars("codeBlock", {
        /**
         * @var userContent.codeBlock.borderRadius
         * @type string|number
         */
        borderRadius: globalVars.border.radius,

        padding: Variables.spacing({
            vertical: globalVars.spacer.componentInner,
            horizontal: globalVars.spacer.componentInner,
        }),

        /**
         * @var userContent.codeBlock.lineHeight
         * @type number
         */
        lineHeight: 1.45,
        /**
         * @var userContent.codeBlock.fg
         * @title Text Color
         * @type string
         * @format hex-color
         */
        fg: blocks.fg,
        /**
         * @var userContent.codeBlock.bg
         * @title Background
         * @type string
         * @format hex-color
         */
        bg: blocks.bg,
    });

    /**
     * @varGroup userContent.list
     * @title User Content - Lists
     */
    const list = makeThemeVars("list", {
        /**
         * @varGroup userContent.list.spacing
         * @title User Content - List - Spacing
         * @expand spacing
         */
        spacing: Variables.spacing({
            top: em(0.5),
            left: em(2),
        }),
        listDecoration: {
            minWidth: em(2),
        },
        nestedList: {
            margin: "0 0 0 1em",
        },
    });

    const spacing = makeThemeVars("spacing", {
        base: 2 * Math.ceil((globalVars.spacer.size * 5) / 8),
    });

    return {
        fonts,
        list,
        blocks,
        code,
        codeInline,
        codeBlock,
        embeds,
        spacing,
        tables,
    };
});
