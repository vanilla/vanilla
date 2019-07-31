/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache, styleFactory, variableFactory, DEBUG_STYLES } from "@library/styles/styleUtils";
import { borders, colorOut, fonts, margins, paddings, singleBorder, unit } from "@library/styles/styleHelpers";
import { border, calc, color, em, important, percent, scale, translateX } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { lineHeightAdjustment } from "@library/styles/textUtils";

export const storyBookVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("storyBook");

    const spacing = makeThemeVars("spacing", {
        large: 24,
        default: 16,
        verticalTitle: 22,
        tight: 8,
    });

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        border: globalVars.mixBgAndFg(0.5),
        primary: globalVars.mixBgAndFg(0.2),
    });

    const gaps = makeThemeVars("gaps", {
        tile: 30,
        wideTile: 60,
    });

    const tiles = makeThemeVars("tiles", {
        height: 120,
        width: 120,
        wideWidth: 240,
    });

    return {
        gaps,
        spacing,
        colors,
        tiles,
    };
});

export const storyBookClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = storyBookVariables();
    const style = styleFactory("storyBookStyles");

    const paragraph = style("paragraph", {
        display: "block",
        ...fonts({
            size: 14,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.normal,
            lineHeight: 1.43,
        }),
        ...margins({ vertical: vars.spacing.default }),
        $nest: lineHeightAdjustment(),
    });

    const heading = style("heading", {
        display: "block",
        ...fonts({
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.bold,
            lineHeight: 1.25,
        }),
        transform: `translateX(${em(globalVars.fonts.alignment.headings.horizontal)})`,
        $nest: lineHeightAdjustment(),
    });

    const headingH1 = style("headingH1", {
        ...fonts({
            size: 24,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.bold,
        }),
        marginBottom: unit(16),
    });

    const headingH2 = style("headingH2", {
        ...fonts({
            size: 18,
        }),
        ...margins({
            vertical: vars.spacing.large,
        }),
        ...paddings({
            bottom: vars.spacing.tight,
            horizontal: unit(vars.spacing.tight / 2),
        }),
        borderBottom: singleBorder({
            width: 1,
            color: vars.colors.border,
        }),
        width: calc(`100% + ${unit(vars.spacing.tight)}`),
        transform: translateX(`-${unit(vars.spacing.tight / 2)}`),

        $nest: {
            [`& + .${paragraph}`]: {
                marginTop: unit(vars.spacing.tight),
            },
        },
    });

    const headingH3 = style("headingH1", {
        ...fonts({
            size: 14,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.semiBold,
        }),
        marginBottom: unit(4),
    });

    const unorderedList = style("unorderedList", {});

    const listItem = style("listItem", {});
    const separator = style("separator", {});
    const link = style("link", {});

    const containerOuter = style("containerOuter", {
        position: "relative",
        display: "block",
        maxWidth: percent(100),
        ...paddings({
            vertical: 55,
            horizontal: 200,
        }),
    });

    const containerInner = style("containerInner", {
        position: "relative",
        display: "block",
    });

    const content = style("content", {
        position: "relative",
        display: "block",
        maxWidth: percent(100),
        width: unit(672),
    });

    const tiles = style("tiles", {
        position: "relative",
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        width: calc(`100% + ${unit(vars.gaps.tile * 2)}`),
        transform: translateX(`-${unit(vars.gaps.tile)}`),
    });

    const tile = style("tile", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        minHeight: unit(vars.tiles.height),
        minWidth: unit(vars.tiles.width),
        margin: unit(vars.gaps.tile),
        ...borders({
            width: 1,
            color: vars.colors.border,
            radius: 0,
        }),
        padding: unit(16),
    });

    const tilesAndText = style("tilesAndText", {
        display: "flex",
        width: percent(100),
        margin: unit(vars.gaps.tile),
        $nest: {
            [`.${tile}`]: {
                margin: 0,
                minWidth: unit(vars.tiles.wideWidth),
            },
        },
    });

    const tileTitle = style("tileTitle", {
        fontSize: unit(14),
    });
    const tileText = style("tileText", {
        width: calc(`100% - ${unit(vars.tiles.width)}`),
    });

    const tileTextPaddingLeft = style("tileTextPaddingLeft", {
        ...paddings({
            vertical: 6,
            left: 28,
        }),
    });

    const setBackground = (type: string) => {
        let background = vars.colors.bg;
        switch (type) {
            case "inverted":
                background = vars.colors.fg;
                break;
            case "primary":
                background = vars.colors.primary;
                break;
        }
        return style("tileType", {
            backgroundColor: colorOut(background),
        });
    };

    const scaleContents = (multiplier: number) => {
        return style("scale", {
            transform: scale(multiplier),
        });
    };

    const compactTilesAndText = style("compactTilesAndText", {
        flexDirection: "column",
        width: unit(vars.tiles.wideWidth),
        $nest: {
            [`.${headingH3}`]: {
                marginTop: unit(vars.spacing.verticalTitle),
            },
            [`.${tileText}`]: {
                width: percent(100),
            },
        },
    });

    return {
        heading,
        headingH1,
        headingH2,
        headingH3,
        paragraph,
        unorderedList,
        listItem,
        separator,
        link,
        containerOuter,
        containerInner,
        tiles,
        tile,
        content,
        scaleContents,
        setBackground,
        tilesAndText,
        tileTitle,
        tileText,
        tileTextPaddingLeft,
        compactTilesAndText,
    };
});
