/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    absolutePosition,
    borders,
    colorOut,
    debugHelper,
    defaultTransition,
    paddings,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { TLength } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { ColorHelper, percent } from "csx";
import { FontSizeProperty, HeightProperty, MarginProperty, PaddingProperty, WidthProperty } from "csstype";

export const tileVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("tiles");

    const spacing = themeVars("spacing", {
        twoColumns: 24,
        threeColumns: 17,
        fourColumns: 17,
        color: globalVars.mainColors.primary as ColorHelper,
    });

    const frame = themeVars("frame", {
        height: 90 as PaddingProperty<TLength>,
        width: 90 as PaddingProperty<TLength>,
        bottomMargin: 16 as MarginProperty<TLength>,
    });

    const title = themeVars("title", {
        fontSize: globalVars.fonts.size.large as FontSizeProperty<TLength>,
        lineHeight: globalVars.lineHeights.condensed,
        marginBottom: 6,
    });

    const description = themeVars("description", {
        fontSize: globalVars.fonts.size.medium as FontSizeProperty<TLength>,
        marginTop: 6,
        lineHeight: globalVars.lineHeights.excerpt,
    });

    const link = themeVars("link", {
        padding: {
            top: 36,
            bottom: 24,
            left: 24,
            right: 24,
        },
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        twoColumnsMinHeight: 0,
        threeColumnsMinHeight: 0,
        fourColumnsMinHeight: 0,
    });

    const fallBackIcon = themeVars("fallBackIcon", {
        width: 90 as WidthProperty<TLength>,
        height: 90 as HeightProperty<TLength>,
        fg: globalVars.mainColors.primary,
    });

    return {
        spacing,
        frame,
        title,
        description,
        link,
        fallBackIcon,
    };
});

export const tileClasses = useThemeCache(() => {
    const vars = tileVariables();
    const globalVars = globalVariables();
    const style = styleFactory("tile");
    const shadow = shadowHelper();

    const root = (columns?: number) => {
        let padding = vars.spacing.twoColumns;

        switch (columns) {
            case 3:
                padding = vars.spacing.threeColumns;
                break;
            case 4:
                padding = vars.spacing.fourColumns;
                break;
        }

        return style({
            display: "flex",
            flexDirection: "column",
            alignItems: "stretch",
            width: percent(100),
            padding: unit(columns === 2 ? vars.spacing.twoColumns : vars.spacing.threeColumns),
            flexGrow: 1,
            ...userSelect(),
        });
    };

    const link = (columns?: number) => {
        let minHeight;

        switch (columns) {
            case 2:
                minHeight = vars.link.twoColumnsMinHeight;
                break;
            case 3:
                minHeight = vars.link.threeColumnsMinHeight;
                break;
            case 4:
                minHeight = vars.link.fourColumnsMinHeight;
                break;
            default:
                minHeight = 0;
        }

        return style("link", {
            ...defaultTransition("box-shadow", "border"),
            ...paddings(vars.link.padding),
            display: "block",
            position: "relative",
            cursor: "pointer",
            flexGrow: 1,
            color: colorOut(globalVars.mainColors.fg),
            backgroundColor: colorOut(vars.link.bg),
            borderRadius: unit(2),
            minHeight: unit(minHeight ?? 0),
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                borders({
                    color: vars.link.fg.fade(0.3),
                }),
                shadow.embed(),
            ),
            textDecoration: "none",
            $nest: {
                "&:hover": {
                    textDecoration: "none",
                    ...shadowOrBorderBasedOnLightness(
                        globalVars.body.backgroundImage.color,
                        borders({
                            color: vars.link.fg.fade(0.5),
                        }),
                        shadow.embedHover(),
                    ),
                },
            },
        });
    };

    const main = style("main", {
        position: "relative",
    });

    const frame = style("iconFrame", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        position: "relative",
        height: unit(vars.frame.height),
        width: unit(vars.frame.width),
        marginTop: "auto",
        marginRight: "auto",
        marginLeft: "auto",
        marginBottom: unit(vars.frame.bottomMargin),
    });

    const icon = style("icon", {
        display: "block",
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: "auto",
        height: "auto",
        maxWidth: percent(100),
        maxHeight: percent(100),
    });

    const title = style("title", {
        fontSize: unit(vars.title.fontSize),
        lineHeight: vars.title.lineHeight,
        textAlign: "center",
        marginBottom: unit(vars.title.marginBottom),
    });

    const description = style("description", {
        position: "relative",
        marginTop: unit(vars.description.marginTop),
        fontSize: unit(vars.description.fontSize),
        lineHeight: vars.description.lineHeight,
        textAlign: "center",
        display: "none",
    });

    const fallBackIcon = style("fallbackIcon", {
        ...absolutePosition.middleOfParent(),
        width: unit(vars.fallBackIcon.width),
        height: unit(vars.fallBackIcon.height),
        color: vars.fallBackIcon.fg.toString(),
    });

    return {
        root,
        link,
        frame,
        icon,
        main,
        title,
        description,
        fallBackIcon,
    };
});
