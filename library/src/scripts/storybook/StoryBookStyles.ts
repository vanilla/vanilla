/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { styleFactory, variableFactory, DEBUG_STYLES } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { importantUnit, negative, singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, em, percent, scale, translateX } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { iconVariables } from "@library/icons/iconStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const storyBookVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("storyBook");

    const spacing = makeThemeVars("spacing", {
        extraLarge: 40,
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

    const outerContainer = makeThemeVars("outerContainer", {
        paddings: {
            vertical: 50,
            horizontal: 55,
        },
    });

    return {
        gaps,
        spacing,
        colors,
        tiles,
        outerContainer,
    };
});

export const storyBookClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = storyBookVariables();
    const style = styleFactory("storyBook");

    const paragraph = style("paragraph", {
        display: "block",
        ...Mixins.font({
            size: 14,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.normal,
            lineHeight: 1.43,
        }),
        ...Mixins.margin({ bottom: vars.spacing.default }),
        ...{
            ...lineHeightAdjustment(),
            [`& + &`]: {
                ...Mixins.margin({ top: vars.spacing.default }),
            },
            [`a`]: {
                textDecoration: "underline",
            },
            [`code`]: {
                backgroundColor: "rgba(0, 0, 0, .04)",
                padding: ".2em .4em",
                fontSize: "85%",
                borderRadius: "4px",
            },
        },
    });

    const heading = style("heading", {
        display: "block",
        ...Mixins.font({
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.bold,
            lineHeight: 1.25,
        }),
        transform: `translateX(${em(globalVars.fonts.alignment.headings.horizontalOffset)})`,
    });

    const headingH2 = style("headingH2", {
        ...Mixins.font({
            size: 18,
        }),

        ...Mixins.padding({
            bottom: vars.spacing.tight,
            horizontal: styleUnit(vars.spacing.tight / 2),
        }),
        ...Mixins.margin({
            top: vars.spacing.extraLarge,
            bottom: vars.spacing.tight,
        }),

        borderBottom: singleBorder({
            width: 1,
            color: vars.colors.border,
        }),
        width: calc(`100% + ${styleUnit(vars.spacing.tight / 2)}`),
        ...{
            [`& + *:not(.${paragraph})`]: {
                marginTop: styleUnit(32),
            },
        },
    });

    const headingH1 = style("headingH1", {
        ...Mixins.font({
            size: 24,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.bold,
        }),
        marginBottom: styleUnit(16),
        ...{
            [`.${headingH2}`]: {
                ...Mixins.margin({
                    top: vars.spacing.large,
                }),
            },
        },
    });

    const headingH3 = style("headingH1", {
        ...Mixins.font({
            size: 14,
            family: globalVars.fonts.families.body,
            weight: globalVars.fonts.weights.semiBold,
        }),
        marginBottom: styleUnit(4),
    });

    const unorderedList = style("unorderedList", {});

    const listVars = {
        spacing: {
            top: em(0.5),
            left: em(2),
        },
    };

    const listItem = style("listItem", {
        position: "relative",
        listStylePosition: "inside",
        listStyle: "inside",
        ...Mixins.margin({
            top: listVars.spacing.top,
            left: listVars.spacing.left,
        }),
    });

    const separator = style("separator", {});
    const link = style("link", {});

    const containerOuter = style("containerOuter", {
        position: "relative",
        display: "block",
        maxWidth: percent(100),
        ...Mixins.padding(vars.outerContainer.paddings),
    });

    const containerInner = style("containerInner", {
        position: "relative",
        display: "block",
        width: percent(100),
    });

    const content = style("content", {
        position: "relative",
        display: "block",
        maxWidth: percent(100),
        width: styleUnit(672),
        margin: "auto",
    });

    const smallContent = style("smallContent", {
        position: "relative",
        display: "block",
        maxWidth: percent(100),
        width: styleUnit(216),
    });

    const tiles = style("tiles", {
        position: "relative",
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        width: calc(`100% + ${styleUnit(vars.gaps.tile * 8)}`),
        transform: translateX(`-${styleUnit(vars.gaps.tile * 3.5)}`),
        ...layoutVariables()
            .mediaQueries()
            .oneColumnDown({
                display: "block",
                width: percent(100),
                transform: "none",
            }),
    });

    const tile = style("tile", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        minHeight: styleUnit(vars.tiles.height),
        minWidth: styleUnit(vars.tiles.width),
        margin: styleUnit(vars.gaps.tile),
        ...Mixins.border({
            width: 1,
            color: vars.colors.border,
            radius: 0,
        }),
        padding: styleUnit(16),
    });

    const tilesAndText = style("tilesAndText", {
        display: "flex",
        width: percent(100),
        margin: styleUnit(vars.gaps.tile),
        ...{
            [`.${tile}`]: {
                margin: 0,
                minWidth: styleUnit(vars.tiles.width),
            },
        },
    });

    const tileTitle = style("tileTitle", {
        fontSize: styleUnit(14),
        marginTop: styleUnit(10),
    });
    const tileText = style("tileText", {
        width: calc(`100% - ${styleUnit(vars.tiles.width)}`),
    });

    const tileTextPaddingLeft = style("tileTextPaddingLeft", {
        ...Mixins.padding({
            vertical: 6,
            left: 28,
        }),
    });

    const setBackground = (type: string) => {
        let bg = globalVars.mainColors.bg;
        let fg = globalVars.mainColors.fg;
        const titleBarVars = titleBarVariables();

        switch (type) {
            case "inverted":
                bg = globalVars.mainColors.fg;
                fg = globalVars.mainColors.bg;
                break;
            case "primary":
                bg = globalVars.mainColors.primary;
                fg = globalVars.mainColors.bg;
                break;
            case "titleBar":
                bg = titleBarVars.colors.bg.mix(titleBarVars.colors.fg, 0.5);
                fg = titleBarVars.colors.fg;
                break;
        }
        return style("tileType", {
            backgroundColor: ColorsUtils.colorOut(bg),
            color: ColorsUtils.colorOut(fg),
        });
    };

    const scaleContents = (multiplier: number) => {
        return style("scale", {
            transform: scale(multiplier),
        });
    };

    const compactTilesAndText = style("compactTilesAndText", {
        flexDirection: "column",
        width: styleUnit(vars.tiles.wideWidth),
        ...{
            [`.${headingH3}`]: {
                marginTop: styleUnit(vars.spacing.verticalTitle),
            },
            [`.${tileText}`]: {
                width: percent(100),
            },
            [`.${paragraph}`]: {
                ...Mixins.margin({
                    top: vars.spacing.tight,
                    bottom: 0,
                }),
            },
        },
    });

    const iconVars = iconVariables();
    const smallerLogo = style("smallerLogo", {
        height: importantUnit(iconVars.vanillaLogo.height / 2),
        width: importantUnit(iconVars.vanillaLogo.width / 2),
    });

    const fullPage = style("fullPage", {
        ...Mixins.margin({
            vertical: negative(vars.outerContainer.paddings.vertical),
            horizontal: negative(vars.outerContainer.paddings.horizontal),
        }),
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
        smallContent,
        scaleContents,
        setBackground,
        tilesAndText,
        tileTitle,
        tileText,
        tileTextPaddingLeft,
        compactTilesAndText,
        smallerLogo,
        fullPage,
    };
});
