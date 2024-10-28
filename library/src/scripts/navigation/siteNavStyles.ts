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
import { css, cx } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { SiteNavNodeTypes } from "./SiteNavNodeTypes";
import { CSSObject } from "@emotion/css/types/create-instance";

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
        default: 12,
    });

    return { node, title, nodeToggle, spacer };
});

export const siteNavClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = siteNavVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const style = styleFactory("siteNav");

    const offset = vars.nodeToggle.width - vars.nodeToggle.iconWidth / 2 - vars.spacer.default;
    const root = style(
        {
            position: "relative",
            display: "block",
            zIndex: 1,
        },
        mediaQueries.noBleedDown({
            marginLeft: offset,
            width: `calc(100% - ${offset}px)`,
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
            keyboardFocus: {
                color: ColorsUtils.colorOut(globalVars.links.colors.keyboardFocus),
                outline: "none",
                "& > *": {
                    outline: "auto 2px -webkit-focus-ring-color",
                },
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
            display: "flex",
            alignItems: "center",
            width: calc(`100% + ${styleUnit(vars.nodeToggle.width)}`),
            marginLeft: styleUnit(-vars.nodeToggle.width),
            textAlign: "start",
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

    const labelText = css({
        flex: 1,
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

    const icon = css({
        height: 18,
        width: 18,
        position: "relative",
        transform: "scale(1.25)",
        ...Mixins.margin({ left: 4, right: 8 }),
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        ["&.disabled"]: {
            opacity: 0.5,
        },
    });

    const iconGroup = css({
        display: "flex",
        alignItems: "center",
        gap: 8,

        marginRight: 8,
        "& *": {
            margin: 0,
        },
    });

    const checkMark = cx(
        icon,
        css({
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            ...Mixins.margin({ left: 4, right: 8 }),
        }),
    );

    const badge = css({});

    return {
        root,
        children,
        contents,
        link,
        label,
        labelText,
        spacer,
        toggle,
        buttonOffset,
        activeLink,
        iconGroup,
        icon,
        checkMark,
        badge,
    };
});

export const siteNavNodeDashboardClasses = useThemeCache(
    (active = false, isFirstLevel = false, hasChildren = false) => {
        const globalVars = globalVariables();
        const vars = siteNavVariables();

        const style = styleFactory(SiteNavNodeTypes.DASHBOARD);

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
            display: "flex",
            alignItems: "center",
            width: calc(`100% + ${styleUnit(vars.nodeToggle.width)}`),
            marginLeft: styleUnit(-vars.nodeToggle.width),
            textAlign: "start",
            border: `solid transparent ${styleUnit(vars.node.borderWidth)}`,
            paddingTop: styleUnit(vars.node.padding + vars.node.borderWidth),
            paddingRight: styleUnit(vars.node.padding),
            paddingBottom: styleUnit(vars.node.padding + vars.node.borderWidth),
            paddingLeft: styleUnit(vars.nodeToggle.width - vars.node.borderWidth),
            fontSize: styleUnit(globalVars.fonts.size.medium),
            "& > svg": {
                height: 18,
                width: 18,
            },
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

        const badge = css({
            fontSize: 11,
            display: "inline",
            padding: "0px 4px",
            position: "relative",
            top: -1,
            marginLeft: 6,
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            verticalAlign: "text-bottom",
            border: `1px solid ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
            borderRadius: 4,
        });

        return {
            link,
            label,
            badge,
        };
    },
);
