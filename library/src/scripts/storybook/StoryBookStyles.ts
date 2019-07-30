/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache, styleFactory, variableFactory, DEBUG_STYLES } from "@library/styles/styleUtils";
import { borders, fonts, margins, paddings, singleBorder, unit } from "@library/styles/styleHelpers";
import { border, calc, color, em, important, percent, translateX } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { lineHeightAdjustment } from "@library/styles/textUtils";

export const storyBookVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("storyBook");

    const spacing = makeThemeVars("spacing", {
        large: 24,
        default: 16,
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
    });

    globalVars.findColorMatch("ffffff");

    return {
        gaps,
        spacing,
        colors,
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
        width: calc(`100% + ${unit(vars.gaps.tile * 2)}`),
        transform: translateX(`-${unit(vars.gaps.tile)}`),
    });

    const tile = style("tile", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        minHeight: unit(120),
        minWidth: unit(120),
        margin: unit(vars.gaps.tile),
        ...borders({
            width: 1,
            color: vars.colors.border,
            radius: 0,
        }),
    });

    return {
        heading,
        headingH1,
        headingH2,
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
    };
});
