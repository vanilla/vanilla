/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { negative, allLinkStates } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { percent, px, calc } from "csx";
import { css, CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { SiteNavNodeTypes } from "./SiteNavNodeTypes";

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
        ...globalVars.fontSizeAndWeightVars("large", "bold"),
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
    const mediaQueries = oneColumnVariables().mediaQueries();

    const style = styleFactory("siteNav");

    const root = style(
        {
            position: "relative",
            display: "block",
            zIndex: 1,
        },
        mediaQueries.noBleedDown({
            marginLeft: styleUnit(vars.nodeToggle.width - vars.nodeToggle.iconWidth / 2 - vars.spacer.default),
        }),
    );

    const title = style("title", {
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large", "bold"),
        }),
    });

    const children = style("children", {
        position: "relative",
        display: "block",
    });

    return { root, title, children };
});

export const linkMixin = (base?: CSSObject, useTextColor?: boolean, selector?: string): CSSObject => {
    const globalVars = globalVariables();
    const baseStyles: CSSObject = { ...base };

    const nestedStyles = {
        ...allLinkStates({
            noState: {
                color: ColorsUtils.colorOut(useTextColor ? globalVars.mainColors.fg : globalVars.links.colors.default),
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

export const siteNavNodeClasses = useThemeCache((active = false, isFirstLevel = false, hasChildren = false) => {
    const globalVars = globalVariables();
    const vars = siteNavVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const style = styleFactory("siteNavNode");

    const root = style({
        position: "relative",
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        fontSize: styleUnit(vars.node.fontSize),
        color: vars.node.fg.toString(),
    });

    const children = style("children", {
        marginLeft: styleUnit(vars.spacer.default),
    });

    const link = css({
        ...(isFirstLevel
            ? Mixins.font({
                  ...globalVars.fontSizeAndWeightVars("large", "normal"),
              })
            : {}),
        ...linkMixin(
            {
                display: "block",
                flexGrow: 1,
                lineHeight: vars.node.lineHeight,
                minHeight: px(30),
                padding: 0,
                width: percent(100),
            },
            true,
        ),
    });

    const label = css(
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
            ...(active ? { color: vars.node.active.fg.toString() } : {}),
            ...(hasChildren ? { fontWeight: globalVars.fonts.weights.bold } : {}),
        },
        mediaQueries.oneColumnDown({
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("large"),
            }),
        }),
    );

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

    const contents = style("contents", {
        display: "block",
        width: percent(100),
        ...{
            [`.${buttonOffset}`]: {
                top: styleUnit(15.5),
            },
        },
    });

    const activeLink = style("active", {
        fontWeight: globalVars.fonts.weights.semiBold,
        color: ColorsUtils.colorOut(globalVars.links.colors.active),
    });

    const checkMark = style("checkMark", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        ...Mixins.margin({ left: 8, right: 4 }),
    });

    return {
        root,
        children,
        contents,
        link,
        label,
        spacer,
        toggle,
        buttonOffset,
        activeLink,
        checkMark,
    };
});

export const siteNavNodeDashboardClasses = useThemeCache(
    (active = false, isFirstLevel = false, hasChildren = false) => {
        const globalVars = globalVariables();
        const vars = siteNavVariables();

        const style = styleFactory(SiteNavNodeTypes.DASHBOARD);

        const root = style("dashboard", {
            position: "relative",
            display: "flex",
            alignItems: "flex-start",
            justifyContent: "flex-start",
            flexWrap: "nowrap",
            fontSize: styleUnit(vars.node.fontSize),
            color: vars.node.fg.toString(),
        });

        const children = style("children", {
            marginLeft: styleUnit(vars.spacer.default),
        });

        const link = css({
            ...(isFirstLevel
                ? Mixins.font({
                      size: styleUnit(globalVars.fonts.size.medium),
                      weight: globalVars.fonts.weights.semiBold,
                      transform: "uppercase",
                  })
                : {}),
            ...linkMixin(
                {
                    display: "block",
                    flexGrow: 1,
                    lineHeight: vars.node.lineHeight,
                    minHeight: px(30),
                    padding: 0,
                    width: percent(100),
                },
                true,
            ),
            overflow: "visible !important",
        });

        const label = css({
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
            fontSize: styleUnit(globalVars.fonts.size.medium),
            ...(active
                ? {
                      borderTopLeftRadius: 6,
                      borderBottomLeftRadius: 6,
                      backgroundColor: "#e8ecf2",
                  }
                : {}),
            ...(hasChildren
                ? {
                      fontWeight: globalVars.fonts.weights.semiBold,
                  }
                : {}),
        });

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
            font: "initial",
            lineHeight: 0,
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

        const contents = style("contents", {
            display: "block",
            width: percent(100),
            ...{
                [`.${buttonOffset}`]: {
                    top: styleUnit(15.5),
                },
            },
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
            label,
            spacer,
            toggle,
            buttonOffset,
            activeLink,
        };
    },
);
