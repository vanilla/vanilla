/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { allLinkStates, colorOut, margins, unit } from "@library/styles/styleHelpers";
import { calc } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const metasVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("metas");

    const fonts = makeThemeVars("fonts", {
        size: globalVars.fonts.size.small,
    });

    const colors = makeThemeVars("color", {
        fg: globalVars.mixBgAndFg(0.85),
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
    const flexContents = overwrites.flexContents ? flexed : {};
    return {
        display: "block",
        lineHeight: globalVars.lineHeights.meta,
        color: colorOut(vars.colors.fg),
        width: calc(`100% + ${unit(vars.spacing.default * 2)}`),
        overflow: "hidden",
        textAlign: "left",
        ...margins({
            left: -vars.spacing.default,
            right: vars.spacing.default,
        }),
        $nest: {
            a: {
                ...allLinkStates({
                    allStates: {
                        textShadow: "none",
                    },
                    noState: {
                        color: colorOut(vars.colors.fg),
                    },
                    hover: {
                        color: colorOut(vars.colors.hover.fg),
                    },
                    focus: {
                        color: colorOut(vars.colors.focus.fg),
                    },
                    active: {
                        color: colorOut(vars.colors.active.fg),
                    },
                }),
            },
            "&.isFlexed": flexed,
        },
        ...overwrites,
        ...flexContents,
    } as NestedCSSProperties;
};

export const metasClasses = useThemeCache(() => {
    const vars = metasVariables();
    const globalVars = globalVariables();
    const style = styleFactory("metas");

    const root = style(metaContainerStyles());

    const meta = style("meta", {
        display: "inline-block",
        fontSize: unit(vars.fonts.size),
        color: colorOut(vars.colors.fg),
        ...margins({
            top: 0,
            right: vars.spacing.default,
            bottom: 0,
            left: vars.spacing.default,
        }),
        $nest: {
            "& &": {
                margin: 0,
            },
        },
    });

    // Get styles of meta, without the margins
    const metaStyle = style("metaStyles", {
        display: "inline-block",
        fontSize: unit(vars.fonts.size),
        color: colorOut(vars.colors.fg),
    });

    const draftStatus = style("draftStatus", {
        flexGrow: 1,
        textAlign: "left",
    });

    return {
        root,
        meta,
        metaStyle,
        draftStatus,
    };
});
