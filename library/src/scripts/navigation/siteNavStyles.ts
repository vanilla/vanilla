/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { negative, allLinkStates } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { percent, px, calc } from "csx";
import { CSSObject } from "@emotion/css";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";

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
            marginTop: styleUnit(negative(vars.nodeToggle.height / 2 - vars.node.fontSize / 2)),
        },
        mediaQueries.noBleedDown({
            marginLeft: styleUnit(vars.nodeToggle.width - vars.nodeToggle.iconWidth / 2 - vars.spacer.default),
        }),
    );

    const title = style("title", {
        fontSize: styleUnit(globalVars.fonts.size.large),
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
            width: calc(`100% + ${styleUnit(vars.nodeToggle.width)}`),
            marginLeft: styleUnit(-vars.nodeToggle.width),
            textAlign: "left",
            border: `solid transparent ${styleUnit(vars.node.borderWidth)}`,
            paddingTop: styleUnit(vars.node.padding + vars.node.borderWidth),
            paddingRight: styleUnit(vars.node.padding),
            paddingBottom: styleUnit(vars.node.padding + vars.node.borderWidth),
            paddingLeft: styleUnit(vars.nodeToggle.width - vars.node.borderWidth),
        },
        mediaQueries.oneColumnDown({
            fontSize: styleUnit(globalVars.fonts.size.large),
        }),
    );

    const root = style({
        position: "relative",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        fontSize: styleUnit(vars.node.fontSize),
        color: vars.node.fg.toString(),
        ...{
            [`&.isCurrent .${label}`]: {
                color: vars.node.active.fg.toString(),
            },
        },
    });

    const children = style("children", {
        marginLeft: styleUnit(vars.spacer.default),
    });

    const contents = style("contents", {
        display: "block",
        width: percent(100),
        ...{
            ".siteNavNode-buttonOffset": {
                top: styleUnit(15.5),
            },
        },
    });

    const linkMixin = (useTextColor?: boolean, selector?: string): CSSObject => {
        const nestedStyles = {
            ...allLinkStates({
                noState: {
                    color: ColorsUtils.colorOut(
                        useTextColor ? globalVars.mainColors.fg : globalVars.links.colors.default,
                    ),
                },
                hover: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.hover),
                },
                focus: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.focus),
                },
                keyboardFocus: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.keyboardFocus),
                },
                active: {
                    color: ColorsUtils.colorOut(globalVars.links.colors.active),
                },
            }),
            "&:not(.focus-visible):active": {
                outline: 0,
            },
            "&:focus": {
                outline: 0,
            },
            "&.hasChildren": {
                ...{
                    [`.${label}`]: {
                        fontWeight: globalVars.fonts.weights.bold,
                    },
                    "&.isFirstLevel": {
                        fontSize: styleUnit(globalVars.fonts.size.large),
                        fontWeight: globalVars.fonts.weights.normal,
                    },
                },
            },
        } as any;

        if (selector) {
            const selectors = selector.split(",");
            if (selectors.length && selectors.length > 0) {
                selectors.map((s) => {
                    cssOut(trimTrailingCommas(s), nestedStyles);
                });
            } else {
                cssOut(trimTrailingCommas(selector), nestedStyles);
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
                baseStyles["color"] = ColorsUtils.colorOut(globalVars.mainColors.fg);
            }
            return baseStyles;
        } else {
            return {
                ...baseStyles,
                ...nestedStyles,
            };
        }
    };

    const link = style("link", linkMixin(true));

    const spacer = style("spacer", {
        display: "block",
        height: styleUnit(vars.nodeToggle.height),
        width: styleUnit(vars.spacer.default),
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
        height: styleUnit(vars.nodeToggle.height),
        width: styleUnit(vars.nodeToggle.width),
    });

    const buttonOffset = style("buttonOffset", {
        position: "relative",
        display: "flex",
        justifyContent: "flex-end",
        width: styleUnit(vars.nodeToggle.width),
        marginLeft: styleUnit(-vars.nodeToggle.width),
        top: px(16),
        transform: `translateY(-50%)`,
    });

    const activeLink = style("active", {
        fontWeight: globalVars.fonts.weights.semiBold,
        color: ColorsUtils.colorOut(globalVars.links.colors.active),
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
        activeLink,
    };
});
