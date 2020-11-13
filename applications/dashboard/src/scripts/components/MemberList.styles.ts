/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import {
    fonts,
    unit,
    colorOut,
    paddings,
    singleBorder,
    EMPTY_BORDER,
    EMPTY_SPACING,
    EMPTY_FONTS,
    borders,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc, percent } from "csx";
import { userPhotoVariables, userPhotoClasses } from "@library/headers/mebox/pieces/userPhotoStyles";
import { BorderBottomProperty } from "csstype";
import { TLength } from "typestyle/lib/types";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { TextTransformProperty } from "csstype";
import { IAllLayoutMediaQueries } from "@library/layout/types/interface.panelLayout";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { makeTestConfig } from "build/scripts/configs/makeTestConfig";

export const memberListVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("memberList", forcedVars);
    const globalVars = globalVariables();

    const spacing = makeVars("spacing", {
        padding: {
            all: globalVars.gutter.half,
        },
    });

    const separator = makeVars("separatort", {
        fg: globalVars.separator.color,
        width: globalVars.separator.size,
    });

    const label = makeVars("label", {
        border: {
            ...EMPTY_BORDER,
            color: globalVars.mainColors.primary,
            radius: 3,
        },
        padding: {
            ...EMPTY_SPACING,
            horizontal: 7,
        },
        font: {
            ...EMPTY_FONTS,
            color: globalVars.mainColors.primary,
            size: 10,
            transform: "uppercase" as TextTransformProperty,
        },
    });

    const head = makeVars("head", {
        padding: {
            vertical: 4,
            horizontal: globalVars.gutter.half,
        },
    });

    const columns = makeVars("column", {
        posts: {
            minWidth: 100,
        },
        lastActive: {
            minWidth: 100,
        },
    });

    return { spacing, separator, label, head, columns };
});

export const memberListClasses = useThemeCache(() => {
    const style = styleFactory("memberList");
    const globalVars = globalVariables();
    const vars = memberListVariables();

    const root = style("root", {
        ...paddings(vars.spacing.padding),
        borderBottom: singleBorder({
            color: vars.separator.fg,
            width: vars.separator.width,
        }) as BorderBottomProperty<TLength>,
    });

    const infoColumn = style("infoColumn", {});

    const user = style("user", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        width: percent(100),
    });

    const info = style("info", {
        display: "flex",
        flexDirection: "row",
        width: percent(100),
    });

    const mainContentCompact = style("mainContentCompact", {});

    const mainContent = style("mainContent", {
        display: "flex",
        justifyContent: "flex-start",
        alignItems: "center",
        paddingLeft: unit(12),
        maxWidth: calc(`100% - ${unit(userPhotoVariables().sizing.medium)}`),
        minHeight: unit(userPhotoVariables().sizing.medium),
        $nest: {
            [`&.${mainContentCompact}`]: {
                flexDirection: "column",
                justifyContent: "space-between",
                alignItems: "flex-start",
            },
        },
    });

    const align = style("align", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
    });

    const linkColors = clickableItemStates();
    const profileLink = style("profileLink", {
        ...fonts({
            size: globalVars.fonts.size.large,
        }),
        marginRight: unit(globalVars.gutter.size),
        $nest: linkColors.$nest,
    });

    const cell = style("container", {
        ...paddings(vars.spacing.padding),
    });

    const isLeft = style("isLeft", {
        $nest: {
            "&&": { paddingLeft: unit(globalVars.gutter.size) },
        },
    });

    const isRight = style("isRight", {
        verticalAlign: "top",
        $nest: {
            "&&": { paddingRight: unit(globalVars.gutter.size) },
        },
    });

    const posts = style("posts", {
        // width: percent(30),
        textAlign: "center",
    });

    const date = style("date", {
        ...fonts({
            size: globalVars.fonts.size.large,
            color: globalVars.meta.text.color,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        whiteSpace: "nowrap",
        textAlign: "center",
    });

    const postsUserSection = style("postsUserSection", {
        fontSize: globalVars.fonts.size.small,
        textTransform: "uppercase",
    });

    const label = style("label", {
        display: "inline-flex",
        ...fonts(vars.label.font),
        ...paddings(vars.label.padding),
        ...borders(vars.label.border),
        alignItems: "center",
        minHeight: unit(globalVars.fonts.size.large),
        flexShrink: 1,
        lineHeight: 1,
    });

    const table = style("table", {
        width: percent(100),
    });

    const mainColumn = style("mainColumn", {});

    const postsColumn = style("postsColumn", {
        minWidth: unit(vars.columns.posts.minWidth),
        verticalAlign: "top",
    });

    const lastActiveColumn = style("lastActiveColumn", {
        minWidth: unit(vars.columns.lastActive.minWidth),
    });

    const head = style("head", {
        ...paddings(vars.head.padding),
        ...fonts({
            size: globalVars.fonts.size.medium,
            weight: globalVars.fonts.weights.semiBold,
            transform: "uppercase",
        }),
        whiteSpace: "nowrap",
        borderBottom: singleBorder({
            color: vars.separator.fg,
            width: vars.separator.width,
        }) as BorderBottomProperty<TLength>,
    });

    const leftAlign = style("leftAlign", {
        textAlign: "left",
    });

    const minHeight = style("minHeight", {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        minHeight: unit(userPhotoVariables().sizing.medium),
    });

    return {
        user,
        mainContent,
        align,
        root,
        head,
        info,
        cell,
        isLeft,
        isRight,
        date,
        posts,
        postsUserSection,
        label,
        table,
        postsColumn,
        lastActiveColumn,
        mainColumn,
        profileLink,
        infoColumn,
        leftAlign,
        minHeight,
        mainContentCompact,
    };
});
