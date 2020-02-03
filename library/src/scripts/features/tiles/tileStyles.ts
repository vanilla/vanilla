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
import { ColorHelper, percent, color } from "csx";
import { FontSizeProperty, HeightProperty, MarginProperty, PaddingProperty, WidthProperty } from "csstype";
import { TileAlignment } from "@library/features/tiles/Tiles";
import { tilesVariables } from "@library/features/tiles/tilesStyles";

export const tileVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("tile");
    const tileVariables = tilesVariables();

    const options = themeVars("options", {
        alignment: TileAlignment.CENTER,
    });

    const spacing = themeVars("spacing", {
        twoColumns: 24,
        threeColumns: 9,
        fourColumns: 17,
        color: globalVars.mainColors.primary as ColorHelper,
    });

    let frameHeight = 90;
    let frameWidth = 90;

    if (tileVariables.options.columns >= 3) {
        frameHeight = 72;
        frameWidth = 72;
    }

    const frame = themeVars("frame", {
        height: frameHeight as PaddingProperty<TLength>,
        width: frameWidth as PaddingProperty<TLength>,
        marginBottom: 16 as MarginProperty<TLength>,
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
        borderRadius: 2,
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        bgHover: globalVars.mainColors.bg,
        bgImage: undefined as string | undefined,
        bgImageHover: undefined as string | undefined,
        twoColumnsMinHeight: 0,
        threeColumnsMinHeight: 0,
        fourColumnsMinHeight: 0,
    });

    const fallBackIcon = themeVars("fallBackIcon", {
        width: frame.height,
        height: frame.width,
        fg: globalVars.mainColors.primary,
    });

    return {
        options,
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

    const root = style({
        display: "flex",
        flexDirection: "column",
        alignItems: "stretch",
        width: percent(100),
        flexGrow: 1,
        margin: "auto",
        ...userSelect(),
    });

    const link = useThemeCache((columns?: number) => {
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
            background: colorOut(vars.link.bgImage),
            borderRadius: unit(vars.link.borderRadius),
            minHeight: unit(minHeight ?? 0),
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                borders({
                    color: vars.link.fg.fade(0.3),
                }),
                shadow.embed(),
            ),
            textDecoration: "none",
            boxSizing: "border-box",
            ...defaultTransition("background", "backgroundColor", "box-shadow"),
            $nest: {
                "&:hover": {
                    backgroundColor: colorOut(vars.link.bgHover),
                    background: colorOut(vars.link.bgImageHover),
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
    });

    const main = style("main", {
        position: "relative",
    });

    const { height, width } = vars.frame;
    const frame = style("iconFrame", {
        display: "flex",
        alignItems: "center",
        justifyContent: vars.options.alignment,
        position: "relative",
        height: unit(height),
        width: unit(width),
        marginTop: "auto",
        marginRight: "auto",
        marginLeft: vars.options.alignment === TileAlignment.CENTER ? "auto" : undefined,
        marginBottom: unit(vars.frame.marginBottom),
    });

    const icon = style("icon", {
        display: "block",
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: vars.options.alignment === TileAlignment.CENTER ? "auto" : undefined,
        height: "auto",
        maxWidth: percent(100),
        maxHeight: percent(100),
    });

    const title = style("title", {
        fontSize: unit(vars.title.fontSize),
        lineHeight: vars.title.lineHeight,
        textAlign: vars.options.alignment,
        marginBottom: unit(vars.title.marginBottom),
    });

    const description = style("description", {
        position: "relative",
        marginTop: unit(vars.description.marginTop),
        fontSize: unit(vars.description.fontSize),
        lineHeight: vars.description.lineHeight,
        textAlign: vars.options.alignment,
    });

    const fallBackIcon = style(
        "fallbackIcon",
        {
            width: unit(vars.fallBackIcon.width),
            height: unit(vars.fallBackIcon.height),
            color: vars.fallBackIcon.fg.toString(),
        },
        vars.options.alignment === TileAlignment.CENTER ? absolutePosition.middleOfParent() : {},
    );

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
