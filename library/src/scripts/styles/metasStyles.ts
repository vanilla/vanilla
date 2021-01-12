/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { allLinkStates, defaultTransition, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, important } from "csx";
import { CSSObject } from "@emotion/css";
import { tagVariables } from "@library/styles/tagStyles";

export const metasVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("metas");

    const fonts = makeThemeVars("fonts", {
        size: globalVars.fonts.size.small,
    });

    const colors = makeThemeVars("color", {
        fg: globalVars.meta.text.color,
        hover: {
            fg: globalVars.links.colors.active,
        },
        focus: {
            fg: globalVars.links.colors.active,
        },
        active: {
            fg: globalVars.links.colors.active,
        },
        deleted: globalVars.messageColors.deleted,
    });

    const text = makeThemeVars("text", {
        margin: 4,
        lineHeight: globalVars.lineHeights.base,
    });

    const spacing = makeThemeVars("spacing", {
        verticalMargin: 24,
        default: globalVars.gutter.quarter,
    });

    return {
        fonts,
        colors,
        text,
        spacing,
    };
});

export const metaContainerStyles = (overwrites?: any) => {
    const vars = metasVariables();
    const globalVars = globalVariables();
    const flexed = { display: "flex", flexWrap: "wrap", justifyContent: "flex-start", alignItems: "center" };
    return {
        display: "block",
        lineHeight: globalVars.lineHeights.meta,
        color: ColorsUtils.colorOut(vars.colors.fg),
        width: calc(`100% + ${styleUnit(vars.spacing.default * 2)}`),
        overflow: "initial", // We can't hide overflow or stuff like user cards will not be shown.
        textAlign: "left",
        fontSize: styleUnit(globalVars.meta.text.size),
        ...Mixins.margin({
            left: -vars.spacing.default,
            right: vars.spacing.default,
        }),
        ...{
            a: {
                ...allLinkStates({
                    allStates: {
                        textShadow: "none",
                    },
                    noState: {
                        color: ColorsUtils.colorOut(vars.colors.fg),
                    },
                    hover: {
                        color: ColorsUtils.colorOut(vars.colors.hover.fg),
                    },
                    focus: {
                        color: ColorsUtils.colorOut(vars.colors.focus.fg),
                    },
                    active: {
                        color: ColorsUtils.colorOut(vars.colors.active.fg),
                    },
                }),
            },
            "&.isFlexed": flexed,
        },
        ...overwrites,
    };
};

export const metaItemStyle = useThemeCache(() => {
    const vars = metasVariables();
    return {
        display: "inline-block",
        fontSize: styleUnit(vars.fonts.size),
        color: ColorsUtils.colorOut(vars.colors.fg),
        ...Mixins.margin({
            top: 0,
            right: vars.spacing.default,
            bottom: 0,
            left: vars.spacing.default,
        }),
        ...{
            "& &": {
                margin: 0,
            },
            ".isDeleted, &.isDeleted": {
                color: ColorsUtils.colorOut(vars.colors.deleted.fg),
            },
        },
    };
});

export const metasClasses = useThemeCache(() => {
    const vars = metasVariables();
    const tagVars = tagVariables();
    const globalVars = globalVariables();
    const style = styleFactory("metas");

    const root = style(metaContainerStyles());
    const meta = style("meta", metaItemStyle());
    const metaLink = style("meta", { ...metaItemStyle(), fontWeight: globalVars.fonts.weights.semiBold });

    const metaIcon = style("metaIcon", {
        ...metaItemStyle(),
        maxHeight: 14,
        ...{
            "& svg": {
                display: "inline-block",
                marginBottom: -6,
            },
        },
    });

    const metaLabel = style("metaLabel", {
        display: "inline-block",
        maxWidth: "100%",
        whiteSpace: "normal",
        textOverflow: "ellipsis",
        ...userSelect(),
        ...Mixins.padding(tagVars.padding),
        ...Mixins.border(tagVars.border),
        ...Mixins.font(tagVars.font),
        ...defaultTransition("border"),
        ...Mixins.margin(tagVars.margin),
    });

    // Get styles of meta, without the margins
    const metaStyle = style("metaStyles", {
        display: "inline-block",
        fontSize: styleUnit(vars.fonts.size),
        color: ColorsUtils.colorOut(vars.colors.fg),
    });

    const draftStatus = style("draftStatus", {
        flexGrow: 1,
        textAlign: "left",
    });

    const noUnderline = style("noUnderline", {
        textDecoration: important("none"),
    });

    const inlineBlock = style("inlineBlock", {
        display: "inline-flex",
        borderTop: "none !important",
        ...{
            "& *:hover, & *:focus, & .isFocused": {
                color: `${globalVars.links.colors.default} !important`,
                backgroundColor: "transparent !important",
            },
        },
    });

    return {
        root,
        meta,
        metaLabel,
        metaLink,
        metaIcon,
        metaStyle,
        draftStatus,
        noUnderline,
        inlineBlock,
    };
});
