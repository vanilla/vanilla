/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc, percent } from "csx";
import { userPhotoVariables, userPhotoClasses } from "@library/headers/mebox/pieces/userPhotoStyles";
import { BorderBottomProperty } from "csstype";
import { TLength } from "@library/styles/styleShim";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { TextTransformProperty } from "csstype";
import { IAllLayoutMediaQueries } from "@library/layout/types/interface.panelLayout";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
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
        border: Variables.border({
            color: globalVars.mainColors.primary,
            radius: 3,
        }),
        padding: Variables.spacing({
            horizontal: 7,
        }),
        font: Variables.font({
            color: globalVars.mainColors.primary,
            size: 10,
            transform: "uppercase",
        }),
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
        ...Mixins.padding(vars.spacing.padding),
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
        paddingLeft: styleUnit(12),
        maxWidth: calc(`100% - ${styleUnit(userPhotoVariables().sizing.medium)}`),
        minHeight: styleUnit(userPhotoVariables().sizing.medium),
        ...{
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

    const linkColors = Mixins.clickable.itemState();
    const profileLink = style("profileLink", {
        ...Mixins.font({
            size: globalVars.fonts.size.large,
        }),
        marginRight: styleUnit(globalVars.gutter.size),
        ...linkColors,
    });

    const cell = style("container", {
        ...Mixins.padding(vars.spacing.padding),
    });

    const isLeft = style("isLeft", {
        "&&": {
            paddingLeft: styleUnit(globalVars.gutter.size),
        },
    });

    const isRight = style("isRight", {
        verticalAlign: "top",
        "&&": {
            paddingRight: styleUnit(globalVars.gutter.size),
        },
    });

    const posts = style("posts", {
        // width: percent(30),
        textAlign: "center",
    });

    const date = style("date", {
        ...Mixins.font({
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
        ...Mixins.padding(vars.label.padding),
        ...Mixins.font(vars.label.font),
        ...Mixins.border(vars.label.border),
        alignItems: "center",
        minHeight: styleUnit(globalVars.fonts.size.large),
        flexShrink: 1,
        lineHeight: 1,
    });

    const table = style("table", {
        width: percent(100),
    });

    const mainColumn = style("mainColumn", {});

    const postsColumn = style("postsColumn", {
        minWidth: styleUnit(vars.columns.posts.minWidth),
        verticalAlign: "top",
    });

    const lastActiveColumn = style("lastActiveColumn", {
        minWidth: styleUnit(vars.columns.lastActive.minWidth),
    });

    const head = style("head", {
        ...Mixins.padding(vars.head.padding),
        ...Mixins.font({
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
        minHeight: styleUnit(userPhotoVariables().sizing.medium),
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
