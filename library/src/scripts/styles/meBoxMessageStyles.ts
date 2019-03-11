/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    componentThemeVariables,
    debugHelper,
    objectFitWithFallback,
    colorOut,
    unit,
    userSelect,
    paddings,
    states,
    allLinkStates,
} from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/styleUtils";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";
import { calc, percent, quote } from "csx";
import { style } from "typestyle";

export const meBoxMessageVariables = useThemeCache(() => {
    const themeVars = componentThemeVariables("meBoxMessage");
    const spacing = {
        padding: {
            top: 8,
            right: 12,
            bottom: 8,
            left: 12,
        },
        ...themeVars.subComponentStyles("spacing"),
    };

    const imageContainer = {
        width: 40,
        ...themeVars.subComponentStyles("imageContainer"),
    };

    const unreadDot = {
        width: 12,
        ...themeVars.subComponentStyles("unreadDot"),
    };

    return { spacing, imageContainer, unreadDot };
});

export const meBoxMessageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = meBoxMessageVariables();
    const headerVars = vanillaHeaderVariables();
    const debug = debugHelper("meBoxMessage");

    const root = style({
        ...debug.name(),
        display: "block",
        $nest: {
            "& + &": {
                borderTop: `solid 1px ${colorOut(globalVars.border.color)}`,
            },
        },
    });

    const link = style({
        ...userSelect(),
        display: "flex",
        flexWrap: "nowrap",
        color: "inherit",
        ...paddings(vars.spacing.padding),
        $nest: {
            ...allLinkStates({
                allStates: {
                    textShadow: "none",
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
        },
        ...debug.name("link"),
    });

    const imageContainer = style({
        position: "relative",
        width: unit(vars.imageContainer.width),
        height: unit(vars.imageContainer.width),
        flexBasis: unit(vars.imageContainer.width),
        borderRadius: percent(50),
        overflow: "hidden",
        border: `solid 1px ${globalVars.border.color.toString()}`,
        ...debug.name("imageContainer"),
    });

    const image = style({
        ...objectFitWithFallback(),
        ...debug.name("image"),
    });

    const status = style({
        position: "relative",
        width: unit(vars.unreadDot.width),
        flexBasis: unit(vars.unreadDot.width),
        ...debug.name("status"),
        $nest: {
            "&.isUnread": {
                $nest: {
                    "&:after": {
                        ...absolutePosition.middleRightOfParent(),
                        content: quote(""),
                        height: unit(vars.unreadDot.width),
                        width: unit(vars.unreadDot.width),
                        backgroundColor: globalVars.mainColors.primary.toString(),
                        borderRadius: percent(50),
                    },
                },
            },
        },
    });

    const contents = style({
        flexGrow: 1,
        ...paddings({
            left: vars.spacing.padding.left,
            right: vars.spacing.padding.right,
        }),
        maxWidth: calc(`100% - ${unit(vars.unreadDot.width + vars.imageContainer.width)}`),
        ...debug.name("contents"),
    });

    const message = style({
        lineHeight: globalVars.lineHeights.excerpt,
        ...debug.name("message"),
    });

    return { root, link, imageContainer, image, status, contents, message };
});
