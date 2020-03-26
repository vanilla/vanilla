/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { negative, unit, colorOut, allLinkStates } from "@library/styles/styleHelpers";
import { percent, px, calc } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles";

export const siteNavVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("siteNav");

    const node = makeThemeVars("node", {
        fontSize: globalVars.fonts.size.medium,
        fg: globalVars.mainColors.fg,
        lineHeight: globalVars.lineHeights.condensed,
        borderWidth: 1,
        padding: 4,
        active: {
            fg: globalVars.links.colors.default,
            fontWeight: globalVars.fonts.weights.bold,
        },
    });

    const title = makeThemeVars("title", {
        fontSize: globalVars.fonts.size.large,
        fontWeight: globalVars.fonts.weights.bold,
    });

    const nodeToggle = makeThemeVars("nodeToggle", {
        height: node.fontSize * node.lineHeight,
        width: globalVars.gutter.size,
        iconWidth: 7,
    });

    const spacer = makeThemeVars("spacer", {
        default: 7,
    });

    return { node, title, nodeToggle, spacer };
});

export const siteNavClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = siteNavVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const style = styleFactory("siteNav");

    const root = style(
        {
            position: "relative",
            display: "block",
            zIndex: 1,
            marginTop: unit(negative(vars.nodeToggle.height / 2 - vars.node.fontSize / 2)),
        },
        mediaQueries.noBleedDown({
            marginLeft: unit(vars.nodeToggle.width - vars.nodeToggle.iconWidth / 2 - vars.spacer.default),
        }),
    );

    const title = style("title", {
        fontSize: unit(globalVars.fonts.size.large),
        fontWeight: globalVars.fonts.weights.bold,
    });

    const children = style("children", {
        position: "relative",
        display: "block",
    });

    return { root, title, children };
});

export const siteNavNodeClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = siteNavVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const style = styleFactory("siteNavNode");

    const label = style(
        "label",
        {
            position: "relative",
            display: "block",
            width: calc(`100% + ${unit(vars.nodeToggle.width)}`),
            marginLeft: unit(-vars.nodeToggle.width),
            textAlign: "left",
            border: `solid transparent ${unit(vars.node.borderWidth)}`,
            paddingTop: unit(vars.node.padding + vars.node.borderWidth),
            paddingRight: unit(vars.node.padding),
            paddingBottom: unit(vars.node.padding + vars.node.borderWidth),
            paddingLeft: unit(vars.nodeToggle.width - vars.node.borderWidth),
        },
        mediaQueries.oneColumnDown({
            fontSize: unit(globalVars.fonts.size.large),
        }),
    );

    const root = style({
        position: "relative",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        fontSize: unit(vars.node.fontSize),
        color: vars.node.fg.toString(),
        $nest: {
            [`&.isCurrent .${label}`]: {
                color: vars.node.active.fg.toString(),
            },
        },
    });

    const children = style("children", {
        marginLeft: unit(vars.spacer.default),
    });

    const contents = style("contents", {
        display: "block",
        width: percent(100),
        $nest: {
            ".siteNavNode-buttonOffset": {
                top: unit(15.5),
            },
        },
    });

    const linkMixin = (useTextColor?: boolean, selector?: string): NestedCSSProperties => {
        const $nest = {
            ...allLinkStates({
                noState: {
                    color: colorOut(useTextColor ? globalVars.mainColors.fg : globalVars.links.colors.default),
                },
                hover: {
                    color: colorOut(globalVars.links.colors.hover),
                },
                focus: {
                    color: colorOut(globalVars.links.colors.focus),
                },
                keyboardFocus: {
                    color: colorOut(globalVars.links.colors.keyboardFocus),
                },
                active: {
                    color: colorOut(globalVars.links.colors.active),
                },
            }).$nest,
            "&:not(.focus-visible):active, &:focus": {
                outline: 0,
            },
            "&.hasChildren": {
                $nest: {
                    [`& .${label}`]: {
                        fontWeight: globalVars.fonts.weights.bold,
                    },
                    "&.isFirstLevel": {
                        fontSize: unit(globalVars.fonts.size.large),
                        fontWeight: globalVars.fonts.weights.normal,
                    },
                },
            },
        };

        if (selector) {
            const selectors = selector.split(",");
            if (selectors.length && selectors.length > 0) {
                selectors.map(s => {
                    nestedWorkaround(trimTrailingCommas(s), $nest);
                });
            } else {
                nestedWorkaround(trimTrailingCommas(selector), $nest);
            }
        }

        const baseStyles = {
            display: "block",
            flexGrow: 1,
            lineHeight: vars.node.lineHeight,
            minHeight: px(30),
            outline: 0,
            padding: 0,
            width: percent(100),
        };

        if (selector) {
            if (useTextColor) {
                baseStyles["color"] = colorOut(globalVars.mainColors.fg);
            }
            return baseStyles;
        } else {
            return {
                ...baseStyles,
                $nest: $nest,
            };
        }
    };

    const link = style("link", linkMixin(true));

    const spacer = style("spacer", {
        display: "block",
        height: unit(vars.nodeToggle.height),
        width: unit(vars.spacer.default),
        margin: `6px 0`,
    });

    const toggle = style("toggle", {
        margin: `6px 0`,
        padding: 0,
        zIndex: 1,
        display: "block",
        alignItems: "center",
        justifyContent: "center",
        outline: 0,
        height: unit(vars.nodeToggle.height),
        width: unit(vars.nodeToggle.width),
    });

    const buttonOffset = style("buttonOffset", {
        position: "relative",
        display: "flex",
        justifyContent: "flex-end",
        width: unit(vars.nodeToggle.width),
        marginLeft: unit(-vars.nodeToggle.width),
        top: px(16),
        transform: `translateY(-50%)`,
    });

    return {
        root,
        children,
        contents,
        link,
        linkMixin,
        label,
        spacer,
        toggle,
        buttonOffset,
    };
});
