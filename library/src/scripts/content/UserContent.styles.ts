/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { css, CSSObject } from "@emotion/css";
import { TLength } from "@library/styles/styleShim";
import { useThemeCache } from "@library/styles/themeCache";
import { em, important, percent } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { Property } from "csstype";
import { blockQuoteVariables } from "@rich-editor/quill/components/blockQuoteStyles";
import { media } from "@library/styles/styleShim";
import { userContentVariables } from "@library/content/UserContent.variables";
import { LinkDecorationType } from "@library/styles/cssUtilsTypes";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
export function userContentMixin(): CSSObject {
    const vars = userContentVariables();
    const globalVars = globalVariables();

    const headingStyle = (tag: string, fontSize: Property.FontSize<TLength>): CSSObject => {
        return {
            marginTop: styleUnit(vars.spacing.base),
            fontSize,
            ...Mixins.font({
                size: fontSize,
                color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                weight: globalVars.fonts.weights.bold,
            }),
            ...lineHeightAdjustment(),
            lineHeight: globalVars.lineHeights.condensed,
        };
    };

    const headings: CSSObject = {
        "& h1:not(.heading)": headingStyle("h1", vars.fonts.headings.h1),
        "& h2:not(.heading)": headingStyle("h2", vars.fonts.headings.h2),
        "& h3:not(.heading)": headingStyle("h3", vars.fonts.headings.h3),
        "& h4:not(.heading)": headingStyle("h4", vars.fonts.headings.h4),
        "& h5:not(.heading)": headingStyle("h5", vars.fonts.headings.h5),
        "& h6:not(.heading)": headingStyle("h6", vars.fonts.headings.h6),
    };

    const lists: CSSObject = {
        ["& ol, & ul"]: {
            listStylePosition: "inside",
            margin: `1em 0 1em 2em`,
            padding: 0,
        },
        ["& ul"]: {
            listStyle: "disc",
            /**
             * This forces the unordered list to take up the remaining space when
             * an embed is floated. Its important for this element to have the correct
             * width because its child list items use an pseudo element to draw the
             * list marker
             */
            display: "flex",
            flexDirection: "column",
            ...{
                [`& > li`]: {
                    // There are unrelated !important styles in _style.scss which is overrides
                    listStyle: "disc !important",
                    position: "relative",
                    "&::marker": {
                        fontSize: ".85rem",
                    },
                },
                [`& ul > li`]: {
                    listStyle: "circle !important",
                },
                [`& ul ul > li`]: {
                    listStyle: "square !important",
                },
                [`& ul ul ul > li`]: {
                    listStyle: "disc !important",
                },
                [`& ul ul ul ul > li`]: {
                    listStyle: "circle !important",
                },
                [`& ul ul ul ul ul > li`]: {
                    listStyle: "square !important",
                },
                [`& ul ul ul ul ul ul > li`]: {
                    listStyle: "disc !important",
                },
                [`& ol, & ul`]: {
                    margin: vars.list.nestedList.margin,
                },
            },
        },
        ["& ol"]: {
            ...{
                [`& > li`]: {
                    listStyle: "decimal",
                },
                [`& ol > li`]: {
                    listStyle: "lower-alpha",
                },
                [`& ol ol > li`]: {
                    listStyle: "lower-roman",
                },
                [`& ol ol ol > li`]: {
                    listStyle: "decimal",
                },
                [`& ol ol ol ol > li`]: {
                    listStyle: "lower-alpha",
                },
                [`& ol ol ol ol ol > li`]: {
                    listStyle: "lower-roman",
                },
                [`& ol ol ol ol ol ol > li`]: {
                    listStyle: "decimal",
                },
                [`& ol, & ul`]: {
                    margin: vars.list.nestedList.margin,
                },
            },
        },
        [`& li`]: {
            margin: `5px 0`,
            [`&, & *:first-child`]: {
                marginTop: 0,
            },
            [`&, & *:last-child`]: {
                marginBottom: 0,
            },
        },
        [`& .listItemChild::after`]: {
            // Clearfix for floating images in lists.
            content: '""',
            display: "table",
            clear: "both",
        },
    };

    const paragraphSpacing: CSSObject = {
        "& > p": {
            marginTop: 0,
            marginBottom: 0,
            ...{
                "&:not(:first-child)": {
                    marginTop: vars.blocks.margin * 0.5,
                },
                "&:first-child": {
                    ...lineHeightAdjustment(),
                },
            },
        },

        "&& > *:not(:last-child):not(.embedResponsive):not(.emoji)": {
            marginBottom: vars.blocks.margin,
        },

        "&& > *:first-child": {
            marginTop: 0,
            ...{
                "&::before": {
                    marginTop: 0,
                },
            },
        },
    };

    const linkColors = Mixins.clickable.itemState();
    const linkStyle = {
        "& a": {
            fontSize: "inherit",
            color: ColorsUtils.colorOut(linkColors.color as string),
            textDecoration: globalVars.links.linkDecorationType === LinkDecorationType.ALWAYS ? "underline" : undefined,
        },
        "& a:hover": {
            color: ColorsUtils.colorOut(globalVars.links.colors.hover),
            textDecoration: "underline",
        },
        "& a:focus": {
            color: ColorsUtils.colorOut(globalVars.links.colors.focus),
            textDecoration: "underline",
        },
        "& a.focus-visible": {
            color: ColorsUtils.colorOut(globalVars.links.colors.keyboardFocus),
            textDecoration: "underline",
        },
        "& a:active": {
            color: ColorsUtils.colorOut(globalVars.links.colors.active),
            textDecoration: "underline",
        },
    };

    const codeStyles: CSSObject = {
        ".code": {
            position: "relative",
            fontSize: vars.code.fontSize,
            fontFamily: `Menlo, Monaco, Consolas, "Courier New", monospace`,
            maxWidth: percent(100),
            overflowX: "auto",
            margin: 0,
            color: ColorsUtils.colorOut(vars.blocks.fg),
            backgroundColor: ColorsUtils.colorOut(vars.blocks.bg),
            border: "none",
        },
        "&& .codeInline": {
            whiteSpace: "normal",
            ...Mixins.padding(vars.codeInline.padding),
            color: ColorsUtils.colorOut(vars.codeInline.fg),
            backgroundColor: ColorsUtils.colorOut(vars.codeInline.bg),
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
            color: ColorsUtils.colorOut(vars.codeBlock.fg),
            backgroundColor: ColorsUtils.colorOut(vars.codeBlock.bg),
            ...Mixins.padding(vars.codeBlock.padding),
            "&::moz-selection, &&.codeBlock::selection": {
                background: "#c1def1",
            },
        },

        "& code": {
            fontFamily: `Menlo, Monaco, Consolas, "Courier New", monospace`,
        },
    };

    // Blockquotes & spoilers
    // These are temporarily kludged here due to lack of time.
    // They should be fully converted in the future but at the moment
    // Only the bare minimum is convverted in order to make the colors work.
    const spoilersAndQuotes: CSSObject = {
        ".embedExternal-content": {
            borderRadius: vars.embeds.borderRadius,
            ...{
                "&::after": {
                    borderRadius: vars.embeds.borderRadius,
                },
            },
        },
        ".embedText-content": {
            background: ColorsUtils.colorOut(vars.embeds.bg),
            color: ColorsUtils.colorOut(vars.embeds.fg),
            overflow: "hidden",
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                Mixins.border({
                    color: vars.embeds.fg.fade(0.3),
                }),
                shadowHelper().embed(),
            ),
        },
        [`.embedText-title,
          .embedLink-source,
          .embedLink-excerpt`]: {
            color: ColorsUtils.colorOut(vars.embeds.fg ?? vars.blocks.fg),
        },
        ".metaStyle": {
            opacity: 0.8,
        },
        ".embedLoader-box": {
            background: ColorsUtils.colorOut(vars.embeds.bg),
            ...Mixins.border({
                color: vars.embeds.fg.fade(0.3),
            }),
        },
    };

    const embeds: CSSObject = {
        "&& .embedExternal": {
            marginBottom: vars.blocks.margin,
        },
        [`&& .float-left,
          && .float-right`]: {
            marginBottom: "0 !important",
        },
        [`&& .float-left .embedExternal-content,
          && .float-right .embedExternal-content`]: {
            marginBottom: vars.blocks.margin,
        },
    };

    const blockQuoteVars = blockQuoteVariables();

    const blockquotes: CSSObject = {
        ".blockquote": {
            color: ColorsUtils.colorOut(blockQuoteVars.colors.fg),
        },
        ".blockquote-content": {
            ...Mixins.margin({ top: 8 }),
            "&:first-of-type": {
                ...Mixins.margin({ top: 0 }),
            },
        },
    };

    const tables: CSSObject = {
        ".tableWrapper": {
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
            ...Mixins.padding({
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
                  background: ColorsUtils.colorOut(vars.tables.stripeColor),
              }
            : {},
        "& > .tableWrapper th, & > .tableWrapper thead td": {
            fontWeight: globalVars.fonts.weights.bold,
        },

        // Mobile table styles.
        ".mobileTableHead": {
            display: "none",
        },
    };

    const outerBorderMixin = (): CSSObject => {
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
            ...{
                ".tableWrapper": outerBorderMixin(),
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
            ...{
                ".tableWrapper .tableHead": {
                    ...Mixins.absolute.srOnly(),
                },
                ".tableWrapper tr": {
                    display: "block",
                    flexWrap: "wrap",
                    width: percent(100),
                    background: "none !important",
                    marginBottom: vars.blocks.margin,
                    ...outerBorderMixin(),
                },
                ".tableWrapper tr .mobileStripe": vars.tables.striped
                    ? {
                          borderTop: "none",
                          borderBottom: "none",
                          background: ColorsUtils.colorOut(vars.tables.stripeColor),
                      }
                    : {
                          borderTop: "none",
                          borderBottom: "none",
                      },
                // First row.
                ".tableWrapper tr > *:first-child": {
                    borderTop: "none",
                },
                // Last row.
                ".tableWrapper tr > *:last-child": {
                    borderBottom: "none",
                },
                ".tableWrapper .mobileTableHead": {
                    borderBottom: "none",
                },
                ".tableWrapper .mobileTableHead + *": {
                    marginTop: -6,
                    borderTop: "none",
                },
                ".tableWrapper tr > *": {
                    width: percent(100),
                    wordWrap: "break-word",
                    display: "block",
                    borderLeft: "none",
                    borderRight: "none",
                },
                ".tableWrapper tr > :not(.mobileTableHead)": {
                    borderRight: "none",
                },
            },
        },
    );

    const root: CSSObject = {
        position: "relative",
        width: percent(100),
        wordBreak: "break-word",
        ...Mixins.font({
            size: vars.fonts.size,
            lineHeight: globalVars.lineHeights.base,
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        }),
        marginTop: lineHeightAdjustment()["::before"]?.marginTop,
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
        ...embeds,
        ...blockquotes,
        ...linkStyle,
        ...tableOuterRadiusQuery,
        ...tableMobileQuery,
        // These CAN'T be flexed. That breaks margin collapsing.
        display: "block !important",
        "&.Hidden": {
            // In case some legacy code tries to hide us.
            display: "none !important",
        },
    };

    return root;
}

export const userContentClasses = useThemeCache(() => {
    return {
        root: css(userContentMixin()),
    };
});
