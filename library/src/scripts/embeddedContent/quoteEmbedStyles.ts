/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { embedContainerVariables } from "@library/embeddedContent/embedStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { allLinkStates, colorOut, importantUnit, margins, paddings, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { percent } from "csx";

export const quoteEmbedVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("quoteEmbed");

    const title = makeThemeVars("title", {
        padding: 14,
    });

    const userContent = makeThemeVars("userContent", {
        padding: 8,
    });

    const footer = makeThemeVars("footer", {
        height: 44,
    });

    return { title, footer, userContent };
});

export const quoteEmbedClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const embedVars = embedContainerVariables();
    const vars = quoteEmbedVariables();

    const style = styleFactory("quoteEmbed");

    const root = style({
        $nest: {
            "&&": {
                overflow: "visible",
            },
        },
    });

    const body = style("body", {
        display: "block",
        textAlign: "left",
        margin: 0,
        padding: 0,
        $nest: {
            "&::before, &::after": {
                content: "initial",
            },
        },
    });

    const userName = style("userName", {
        fontWeight: globalVars.fonts.weights.bold,
    });

    const title = style("title", {
        ...lineHeightAdjustment(),
        ...margins({
            horizontal: importantUnit(0),
            top: importantUnit(0),
            bottom: importantUnit(globalVars.meta.spacing.default),
        }),
        display: "block",
        width: percent(100),
        color: colorOut(globalVars.mainColors.fg),
        fontSize: globalVars.fonts.size.medium,
        fontWeight: globalVars.fonts.weights.bold,
        lineHeight: globalVars.lineHeights.condensed,
    });

    const isPadded = style("isPadded", {});
    const fixLineHeight = style("fixLineHeight", {});

    const titleLink = style("titleLink", {
        display: "block",
        position: "relative",
        $nest: {
            [`&.${isPadded}`]: {
                paddingTop: unit(vars.title.padding),
            },
        },
    });

    const header = style("header", {
        ...paddings({
            all: embedVars.spacing.padding,
            bottom: 0,
        }),
    });

    const paddingAdjustment = style("paddingAdjustment", {});

    const content = style("content", {
        ...paddings({
            all: embedVars.spacing.padding,
        }),
        width: percent(100),
        $nest: {
            [`&.${paddingAdjustment}`]: {
                ...paddings({
                    all: embedVars.spacing.padding,
                    top: vars.userContent.padding,
                }),
            },
        },
    });

    const footer = style("footer", {
        position: "relative",
        ...paddings({
            horizontal: embedVars.spacing.padding,
        }),
    });

    const footerMain = style("footerMain", {
        display: "flex",
        position: "relative",
        flexWrap: "wrap",
        width: percent(100),
        alignItems: "center",
        justifyContent: "space-between",
        minHeight: unit(vars.footer.height),
    });

    const footerSeparator = style("footerSeparator", {
        // Reset
        border: 0,
        borderStyle: "solid",
        margin: 0,
        display: "block",

        // Styling
        width: percent(100),
        height: unit(1),
        backgroundColor: colorOut(globalVars.mixBgAndFg(0.2)),
    });

    const postLink = style("postLink", {
        display: "flex",
        alignItems: "center",
        marginLeft: "auto",
        ...allLinkStates({
            allStates: {
                textDecoration: "none",
            },
            noState: {
                color: colorOut(globalVars.links.colors.default),
            },
            hover: {
                color: colorOut(globalVars.links.colors.hover),
            },
            focus: {
                color: colorOut(globalVars.links.colors.focus),
            },
            active: {
                color: colorOut(globalVars.links.colors.active),
            },
        }),
    });
    const postLinkIcon = style("postLinkIcon", {
        color: "inherit",
    });

    const discussionLink = style("discussionLink", {
        height: unit(globalVars.icon.sizes.default),
        width: unit(globalVars.icon.sizes.default),
        ...allLinkStates({
            allStates: {
                textDecoration: "none",
            },
            noState: {
                color: colorOut(globalVars.links.colors.default),
            },
            hover: {
                color: colorOut(globalVars.links.colors.hover),
            },
            focus: {
                color: colorOut(globalVars.links.colors.focus),
            },
            active: {
                color: colorOut(globalVars.links.colors.active),
            },
        }),
    });

    const discussionIcon = style("discussionIcon", {
        color: "inherit",
    });

    const blockquote = style("blockquote", {
        margin: 0,
        padding: 0,
        $nest: {
            ".userContent": {
                ...lineHeightAdjustment(),
                fontSize: unit(globalVars.fonts.size.medium),
            },
        },
    });

    return {
        root,
        body,
        userName,
        title,
        titleLink,
        isPadded,
        header,
        content,
        fixLineHeight,
        footer,
        footerMain,
        footerSeparator,
        postLink,
        postLinkIcon,
        discussionLink,
        discussionIcon,
        blockquote,
        paddingAdjustment,
    };
});
