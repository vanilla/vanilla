/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    colorOut,
    margins,
    paddings,
    unit,
    EMPTY_BORDER,
    singleBorder,
    visibility,
    srOnly,
} from "@library/styles/styleHelpers";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { NestedCSSProperties, NestedCSSSelectors, TLength } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { em, important, percent, px, border } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { FontSizeProperty } from "csstype";
import { blockQuoteVariables } from "@rich-editor/quill/components/blockQuoteStyles";
import { cssOut } from "@dashboard/compatibilityStyles";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { media } from "typestyle";
import { IThemeVariables } from "@library/theming/themeReducer";

export enum TableStyle {
    HORIZONTAL_BORDER = "horizontalBorder",
    HORIZONTAL_BORDER_STRIPED = "horizontalBorderStriped",
    VERTICAL_BORDER = "verticalBorder",
    VERTICAL_BORDER_STRIPED = "verticalBorderStriped",
}

export const userContentVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("userContent", forcedVars);
    const globalVars = globalVariables(forcedVars);
    const { mainColors } = globalVars;

    const fonts = makeThemeVars("fonts", {
        size: globalVars.fonts.size.large,
        headings: {
            h1: "2em",
            h2: "1.5em",
            h3: "1.25em",
            h4: "1em",
            h5: ".875em",
            h6: ".85em",
        },
    });

    const tableInit = makeThemeVars("tables", {
        style: TableStyle.HORIZONTAL_BORDER_STRIPED,
        borders: {
            ...EMPTY_BORDER,
            ...globalVars.border,
        },
        cell: {
            alignment: "left" as "center" | "left" | "right",
        },
        mobileBreakpoint: 600,
    });

    const tables = makeThemeVars("tables", {
        ...tableInit,
        striped: [TableStyle.HORIZONTAL_BORDER_STRIPED, TableStyle.VERTICAL_BORDER_STRIPED].includes(tableInit.style),
        stripeColor: globalVars.mixBgAndFg(0.05),
        outerBorderRadius: [TableStyle.VERTICAL_BORDER_STRIPED, TableStyle.VERTICAL_BORDER].includes(tableInit.style)
            ? 4
            : 0,
        horizontalBorders: {
            enabled: true, // All current variants have horizontal borders.
            borders: tableInit.borders,
        },
        verticalBorders: {
            enabled: [TableStyle.VERTICAL_BORDER_STRIPED, TableStyle.VERTICAL_BORDER].includes(tableInit.style),
            borders: tableInit.borders,
        },
    });

    const blocks = makeThemeVars("blocks", {
        margin: fonts.size,
        fg: mainColors.fg,
        bg: globalVars.mixBgAndFg(0.035),
    });

    const embeds = makeThemeVars("embeds", {
        bg: mainColors.bg,
        fg: mainColors.fg,
        borderRadius: px(2),
    });

    const code = makeThemeVars("code", {
        fontSize: em(0.85),
        borderRadius: 2,
    });

    const codeInline = makeThemeVars("codeInline", {
        borderRadius: code.borderRadius,
        paddingVertical: em(0.2),
        paddingHorizontal: em(0.4),
        fg: blocks.fg,
        bg: blocks.bg,
    });

    const codeBlock = makeThemeVars("codeBlock", {
        borderRadius: globalVars.border.radius,
        paddingVertical: fonts.size,
        paddingHorizontal: fonts.size,
        lineHeight: 1.45,
        fg: blocks.fg,
        bg: blocks.bg,
    });

    const list = makeThemeVars("list", {
        spacing: {
            top: em(0.5),
            left: em(2),
        },
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

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
export const userContentClasses = useThemeCache(() => {
    const style = styleFactory("userContent");
    const vars = userContentVariables();
    const globalVars = globalVariables();

    const listItem: NestedCSSProperties = {
        position: "relative",
        ...margins({
            top: vars.list.spacing.top,
            left: vars.list.spacing.left,
        }),
        $nest: {
            "&:first-child": {
                marginTop: 0,
            },
            "&:last-child": {
                marginBottom: 0,
            },
        },
    };

    const headingStyle = (tag: string, fontSize: FontSizeProperty<TLength>): NestedCSSProperties => {
        return {
            marginTop: unit(vars.spacing.base),
            fontSize,
            $nest: lineHeightAdjustment(),
        };
    };

    const headings: NestedCSSSelectors = {
        "& h1:not(.heading)": headingStyle("h1", vars.fonts.headings.h1),
        "& h2:not(.heading)": headingStyle("h2", vars.fonts.headings.h2),
        "& h3:not(.heading)": headingStyle("h3", vars.fonts.headings.h3),
        "& h4:not(.heading)": headingStyle("h4", vars.fonts.headings.h4),
        "& h5:not(.heading)": headingStyle("h5", vars.fonts.headings.h5),
        "& h6:not(.heading)": headingStyle("h6", vars.fonts.headings.h6),
    };

    const lists: NestedCSSSelectors = {
        ["& ol"]: {
            listStylePosition: "inside",
            margin: `0 0 1em 3em`,
            padding: 0,
            $nest: {
                [`& li`]: {
                    listStyle: "decimal",
                },
                [`& ol li`]: {
                    listStyle: "lower-alpha",
                },
                [`& ol ol li`]: {
                    listStyle: "lower-roman",
                },
                [`& ol ol ol li`]: {
                    listStyle: "decimal",
                },
                [`& ol ol ol ol li`]: {
                    listStyle: "lower-alpha",
                },
                [`& ol ol ol ol ol li`]: {
                    listStyle: "lower-roman",
                },
                [`& ol ol ol ol ol ol li`]: {
                    listStyle: "decimal",
                },
                [`& ol, & ul`]: {
                    margin: vars.list.nestedList.margin,
                },
            },
        },
        ["& ul"]: {
            listStylePosition: "inside",
            listStyle: "disc",
            margin: `1em 0 1em 2em`,
            padding: 0,
            $nest: {
                [`& li`]: {
                    listStyle: "none",
                    position: "relative",
                },
                [`& li::before`]: {
                    fontFamily: `'Arial', serif`,
                    content: `"•"`,
                    position: "absolute",
                    left: em(-1),
                },
                [`& ol, & ul`]: {
                    margin: vars.list.nestedList.margin,
                },
            },
        },
        [`& li`]: {
            margin: `5px 0`,
            $nest: {
                [`&, & *:first-child`]: {
                    marginTop: 0,
                },
                [`&, & *:last-child`]: {
                    marginBottom: 0,
                },
            },
        },
    };

    const paragraphSpacing: NestedCSSSelectors = {
        "& > p": {
            marginTop: 0,
            marginBottom: 0,
            $nest: {
                "&:not(:first-child)": {
                    marginTop: vars.blocks.margin * 0.5,
                },
                "&:first-child": {
                    $nest: lineHeightAdjustment(),
                },
            },
        },

        "&& > *:not(:last-child)": {
            marginBottom: vars.blocks.margin,
        },

        "&& > *:first-child": {
            $unique: true, // Required to prevent collapsing in with some other variable.
            marginTop: 0,

            $nest: {
                "&::before": {
                    marginTop: 0,
                },
            },
        },
    };

    const linkColors = clickableItemStates();
    const linkStyle = {
        "& a": {
            color: colorOut(linkColors.color as string),
        },
        "& a:hover": {
            color: colorOut(globalVars.links.colors.hover),
            textDecoration: "underline",
        },
        "& a:focus": {
            color: colorOut(globalVars.links.colors.focus),
            textDecoration: "underline",
        },
        "& a.focus-visible": {
            color: colorOut(globalVars.links.colors.keyboardFocus),
            textDecoration: "underline",
        },
        "& a:active": {
            color: colorOut(globalVars.links.colors.active),
            textDecoration: "underline",
        },
    };

    const codeStyles: NestedCSSSelectors = {
        "& .code": {
            position: "relative",
            fontSize: vars.code.fontSize,
            fontFamily: `Menlo, Monaco, Consolas, "Courier New", monospace`,
            maxWidth: percent(100),
            overflowX: "auto",
            margin: 0,
            color: colorOut(vars.blocks.fg),
            backgroundColor: colorOut(vars.blocks.bg),
            border: "none",
        },
        "&& .codeInline": {
            whiteSpace: "normal",
            ...paddings({
                top: vars.codeInline.paddingVertical,
                bottom: vars.codeInline.paddingVertical,
                left: vars.codeInline.paddingHorizontal,
                right: vars.codeInline.paddingHorizontal,
            }),
            color: colorOut(vars.codeInline.fg),
            backgroundColor: colorOut(vars.codeInline.bg),
            borderRadius: vars.codeInline.borderRadius,
            // We CAN'T use display: `inline` & position: `relative` together.
            // This causes the cursor to disappear in a contenteditable.
            // @see https://bugs.chromium.org/p/chromium/issues/detail?id=724821
            display: "inline",
            position: "static",
        },
        "&& .codeBlock": {
            display: "block",
            wordWrap: "normal",
            lineHeight: vars.codeBlock.lineHeight,
            borderRadius: vars.codeBlock.borderRadius,
            flexShrink: 0, // Needed so code blocks don't collapse in the editor.
            whiteSpace: "pre",
            color: colorOut(vars.codeBlock.fg),
            backgroundColor: colorOut(vars.codeBlock.bg),
            ...paddings({
                top: vars.codeBlock.paddingVertical,
                bottom: vars.codeBlock.paddingVertical,
                left: vars.codeBlock.paddingHorizontal,
                right: vars.codeBlock.paddingHorizontal,
            }),
        },
    };

    // Blockquotes & spoilers
    // These are temporarily kludged here due to lack of time.
    // They should be fully converted in the future but at the moment
    // Only the bare minimum is convverted in order to make the colors work.
    const spoilersAndQuotes: NestedCSSSelectors = {
        "& .embedExternal-content": {
            borderRadius: vars.embeds.borderRadius,
            $nest: {
                "&::after": {
                    borderRadius: vars.embeds.borderRadius,
                },
            },
        },
        "& .embedText-content": {
            background: colorOut(vars.embeds.bg),
            color: colorOut(vars.embeds.fg),
            overflow: "hidden",
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                borders({
                    color: vars.embeds.fg.fade(0.3),
                }),
                shadowHelper().embed(),
            ),
        },
        [`& .embedText-title,
          & .embedLink-source,
          & .embedLink-excerpt`]: {
            color: colorOut(vars.blocks.fg),
        },
        "& .metaStyle": {
            opacity: 0.8,
        },
        "& .embedLoader-box": {
            background: colorOut(vars.embeds.bg),
            ...borders({
                color: vars.embeds.fg.fade(0.3),
            }),
        },
    };

    const blockQuoteVars = blockQuoteVariables();

    const blockquotes: NestedCSSSelectors = {
        ".blockquote": {
            color: colorOut(blockQuoteVars.colors.fg),
        },
    };

    const tables: NestedCSSSelectors = {
        "& .tableWrapper": {
            overflowX: "auto",
            width: percent(100),
        },
        "& > .tableWrapper > table": {
            width: percent(100),
        },
        // Rest of the table styles
        "& > .tableWrapper th": {
            whiteSpace: "nowrap",
        },
        "& > .tableWrapper td, & > .tableWrapper th": {
            overflowWrap: "break-word",
            minWidth: 80,
            ...paddings({
                vertical: 6,
                horizontal: 12,
            }),
            border: "none",
            textAlign: vars.tables.cell.alignment,
            ...(vars.tables.horizontalBorders.enabled
                ? {
                      borderTop: singleBorder(vars.tables.horizontalBorders.borders),
                      borderBottom: singleBorder(vars.tables.horizontalBorders.borders),
                  }
                : {}),
            ...(vars.tables.verticalBorders.enabled
                ? {
                      borderLeft: singleBorder(vars.tables.verticalBorders.borders),
                      borderRight: singleBorder(vars.tables.verticalBorders.borders),
                  }
                : {}),
        },
        "& > .tableWrapper tr:nth-child(even)": vars.tables.striped
            ? {
                  background: colorOut(vars.tables.stripeColor),
              }
            : {},
        "& > .tableWrapper th, & > .tableWrapper thead td": {
            fontWeight: globalVars.fonts.weights.bold,
        },

        // Mobile table styles.
        "& .mobileTableHead": {
            display: "none",
        },
    };

    const outerBorderMixin = (): NestedCSSProperties => {
        return {
            borderRadius: vars.tables.outerBorderRadius,
            borderTop: vars.tables.horizontalBorders.enabled
                ? singleBorder(vars.tables.horizontalBorders.borders)
                : undefined,
            borderBottom: vars.tables.horizontalBorders.enabled
                ? singleBorder(vars.tables.horizontalBorders.borders)
                : undefined,
            borderLeft: vars.tables.verticalBorders.enabled
                ? singleBorder(vars.tables.verticalBorders.borders)
                : undefined,
            borderRight: vars.tables.verticalBorders.enabled
                ? singleBorder(vars.tables.verticalBorders.borders)
                : undefined,
        };
    };

    // Apply outer border radii.
    // border-collapse prevents our outer radius from applying.
    const tableOuterRadiusQuery = media(
        { minWidth: vars.tables.mobileBreakpoint + 1 },
        {
            $nest: {
                "& .tableWrapper": outerBorderMixin(),
                "& > .tableWrapper thead tr:first-child > *, & > .tableWrapper tbody:first-child tr:first-child > *": {
                    // Get rid of the outer border radius.
                    borderTop: "none",
                },
                "& > .tableWrapper :not(thead) tr:last-child > *": {
                    // Get rid of the outer border radius.
                    borderBottom: "none",
                },
                "& > .tableWrapper tr > *:last-child": {
                    // Get rid of the outer border radius.
                    borderRight: "none",
                },
                "& > .tableWrapper tr > *:first-child, & > .tableWrapper tr > .mobileTableHead:first-child + *": {
                    // Get rid of the outer border radius.
                    borderLeft: "none",
                },
            },
        },
    );

    const tableMobileQuery = media(
        { maxWidth: vars.tables.mobileBreakpoint },
        {
            $nest: {
                "& .tableWrapper .tableHead": {
                    ...srOnly(),
                },
                "& .tableWrapper tr": {
                    display: "block",
                    flexWrap: "wrap",
                    width: percent(100),
                    background: "none !important",
                    marginBottom: vars.blocks.margin,
                    ...outerBorderMixin(),
                },
                "& .tableWrapper tr .mobileStripe": vars.tables.striped
                    ? {
                          borderTop: "none",
                          borderBottom: "none",
                          background: colorOut(vars.tables.stripeColor),
                      }
                    : {
                          borderTop: "none",
                          borderBottom: "none",
                      },
                // First row.
                "& .tableWrapper tr > *:first-child": {
                    borderTop: "none",
                },
                // Last row.
                "& .tableWrapper tr > *:last-child": {
                    borderBottom: "none",
                },
                "& .tableWrapper .mobileTableHead": {
                    borderBottom: "none",
                },
                "& .tableWrapper .mobileTableHead + *": {
                    marginTop: -6,
                    borderTop: "none",
                },
                "& .tableWrapper tr > *": {
                    width: percent(100),
                    wordWrap: "break-word",
                    display: "block",
                    borderLeft: "none",
                    borderRight: "none",
                },
                "& .tableWrapper tr > :not(.mobileTableHead)": {
                    borderRight: "none",
                },
            },
        },
    );

    const root = style(
        {
            // These CAN'T be flexed. That breaks margin collapsing.
            display: important("block"),
            position: "relative",
            width: percent(100),
            wordBreak: "break-word",
            lineHeight: globalVars.lineHeights.base,
            fontSize: vars.fonts.size,
            marginTop: lineHeightAdjustment()["&::before"]!.marginTop,
            $nest: {
                // A placeholder might be put in a ::before element. Make sure we match the line-height adjustment.
                "& iframe": {
                    width: percent(100),
                },
                ...tables,
                ...headings,
                ...lists,
                ...paragraphSpacing,
                ...codeStyles,
                ...spoilersAndQuotes,
                ...blockquotes,
                ...linkStyle,
            },
        },
        tableOuterRadiusQuery,
        tableMobileQuery,
    );

    return { root };
});

export const userContentCSS = () => {
    const globalVars = globalVariables();
    cssOut(
        `
        .Container .userContent h1,
        .Container .userContent h2,
        .Container.userContent h3,
        .Container .userContent h4,
        .Container .userContent h5,
        .Container .userContent h6`,
        {
            color: colorOut(globalVars.mainColors.fg),
        },
    );
};
