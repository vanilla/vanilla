/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { NestedCSSProperties, NestedCSSSelectors, TLength } from "typestyle/lib/types";
import { margins, allLinkStates, setAllLinkColors, paddings, colorOut } from "@library/styles/styleHelpers";
import { em, percent } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { FontSizeProperty } from "csstype";

const userContentVariables = useThemeCache(() => {
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

    const code = makeThemeVars("code", {
        fontSize: em(0.85),
        borderRadius: 0,
        // bg target rgba(127, 127, 127, .15);
        bg: mainColors.fg.mix(mainColors.bg, 0.08),
        fg: mainColors.fg.darken(0.2),
    });

    const codeInline = makeThemeVars("codeInline", {
        borderRadius: code.borderRadius,
        paddingVertical: em(0.2),
        paddingHorizontal: em(0.4),
    });

    const codeBlock = makeThemeVars("codeBlock", {
        borderRadius: code.borderRadius,
        paddingVertical: fonts.size,
        paddingHorizontal: fonts.size,
        lineHeight: 1.45,
    });

    const spacing = makeThemeVars("spacing", {
        blockMargin: fonts.size,
    });

    const list = makeThemeVars("list", {
        spacing: {
            top: em(0.5),
            left: em(2),
        },
        listDecoration: {
            minWidth: em(2),
        },
    });

    return { fonts, list, spacing, code, codeInline, codeBlock };
});

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
export const userContentStyles = useThemeCache(() => {
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
            "> &:first-child": {
                marginTop: 0,
            },
            "> &:last-child": {
                marginBottom: 0,
            },
        },
    };

    const headingStyle = (tag: string, fontSize: FontSizeProperty<TLength>): NestedCSSProperties => {
        return {
            marginTop: globalVars.spacer.size,
            fontSize,
            $nest: lineHeightAdjustment(globalVars.lineHeights.condensed),
        };
    };
    const headings: NestedCSSSelectors = {
        "& h1": headingStyle("h1", vars.fonts.headings.h1),
        "& h2": headingStyle("h2", vars.fonts.headings.h2),
        "& h3": headingStyle("h3", vars.fonts.headings.h3),
        "& h4": headingStyle("h4", vars.fonts.headings.h4),
        "& h5": headingStyle("h5", vars.fonts.headings.h5),
        "& h6": headingStyle("h6", vars.fonts.headings.h6),
    };

    const lists: NestedCSSSelectors = {
        "& ol": {
            listStylePosition: "inside",
        },
        "& ol li": {
            ...listItem,
            listStyle: "decimal",
        },
        "& ul li": {
            ...listItem,
            listStyle: "initial",
        },
    };

    const paragraphSpacing: NestedCSSSelectors = {
        "& > *:not(:last-child)": {
            marginBottom: vars.spacing.blockMargin,
        },
        "& > *:first-child": {
            marginTop: 0,
        },

        "& p": {
            marginTop: 0,
            marginBottom: 0,
        },
        "& p:not(:first-child)": {
            marginTop: vars.spacing.blockMargin * 0.5,
        },
        "& p:first-child": {
            $nest: lineHeightAdjustment(globalVars.lineHeights.base),
        },
    };

    const linkStyle = setAllLinkColors({
        hover: {
            textDecoration: "underline",
        },
    });

    const linkStyles: NestedCSSSelectors = {
        "p a": linkStyle,
        "li a": linkStyle,
    };

    const codeStyles: NestedCSSSelectors = {
        "& .code": {
            position: "relative",
            verticalAlign: "middle",
            fontSize: vars.code.fontSize,
            fontFamily: `Menlo, Monaco, Consolas, "Courier New", monospace`,
            maxWidth: percent(100),
            overflowX: "auto",
            margin: 0,
            color: colorOut(vars.code.fg),
            backgroundColor: colorOut(vars.code.bg),
            border: "none",
        },
        "& .codeInline": {
            display: "inline",
            whiteSpace: "normal",
            lineHeight: "inherit",
            ...paddings({
                top: vars.codeInline.paddingVertical,
                bottom: vars.codeInline.paddingVertical,
                left: vars.codeInline.paddingHorizontal,
                right: vars.codeInline.paddingHorizontal,
            }),
            borderRadius: vars.codeInline.borderRadius,
        },
        "& .codeBlock": {
            display: "block",
            wordWrap: "normal",
            lineHeight: vars.codeBlock.lineHeight,
            whiteSpace: "pre",
            ...paddings({
                top: vars.codeBlock.paddingVertical,
                bottom: vars.codeBlock.paddingVertical,
                left: vars.codeBlock.paddingHorizontal,
                right: vars.codeBlock.paddingHorizontal,
            }),
            borderRadius: vars.codeBlock.borderRadius,
        },
    };

    const root = style({
        fontSize: vars.fonts.size,
        display: "block",
        position: "relative",
        width: percent(100),
        wordBreak: "break-word",
        lineHeight: globalVars.lineHeights.base,
        $nest: {
            ...headings,
            ...lists,
            ...paragraphSpacing,
            ...linkStyles,
            ...codeStyles,
        },
    });

    return { root };
});
