/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, colorOut, fonts, margins, paddings, setAllLinkColors, unit } from "@library/styles/styleHelpers";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { NestedCSSProperties, NestedCSSSelectors, TLength } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { em, important, percent, px } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { FontSizeProperty } from "csstype";

export const userContentVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("userContent");
    const globalVars = globalVariables();
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

    const blocks = makeThemeVars("blocks", {
        margin: fonts.size,
        fg: mainColors.fg,
        bg: globalVars.mixBgAndFg(0.05),
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
        },
    };

    const linkColors = setAllLinkColors();
    const linkStyle = {
        color: linkColors.color,
        $nest: {
            ...linkColors.nested,
            "&:hover, &:focus": {
                textDecoration: "underline",
            },
        },
    };

    const linkStyles: NestedCSSSelectors = {
        a: linkStyle,
        "p a": linkStyle,
        "li a": linkStyle,
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

    const root = style({
        // These CAN'T be flexed. That breaks margin collapsing.
        display: important("block"),
        position: "relative",
        width: percent(100),
        wordBreak: "break-word",
        lineHeight: globalVars.lineHeights.base,
        fontSize: vars.fonts.size,
        $nest: {
            // A placeholder might be put in a ::before element. Make sure we match the line-height adjustment.
            "&::before": {
                marginTop: lineHeightAdjustment()["&::before"]!.marginTop,
            },
            ...headings,
            ...lists,
            ...paragraphSpacing,
            ...linkStyles,
            ...codeStyles,
            ...spoilersAndQuotes,
        },
    });

    return { root };
});
