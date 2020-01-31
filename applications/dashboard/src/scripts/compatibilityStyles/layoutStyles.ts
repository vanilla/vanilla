/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { colorOut } from "@library/styles/styleHelpersColors";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { containerMainStyles } from "@library/layout/components/containerStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { paddings } from "@library/styles/styleHelpersfPadding";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, color, percent } from "csx";
import { unit } from "@library/styles/styleHelpers";

export const forumLayoutVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("forumLayout");

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter: globalVars.constants.fullGutter,
        panelWidth: 220, // main calculated based on panel width
        breakPoints: {
            // Other break points are calculated
            // twoColumns: 1200,
            // xs: 500,
        },
    });

    const gutter = makeThemeVars("gutter", {
        full: foundationalWidths.fullGutter, // 48
        size: foundationalWidths.fullGutter / 2, // 24
        halfSize: foundationalWidths.fullGutter / 4, // 12
        quarterSize: foundationalWidths.fullGutter / 8, // 6
    });

    const panel = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
        paddedWidth: foundationalWidths.panelWidth + gutter.full,
    });

    const main = makeThemeVars("main", {
        width: calc(`100% - ${unit(panel.paddedWidth)}`),
    });

    const cell = makeThemeVars("cell", {
        paddings: {
            horizontal: 8,
            vertical: 12,
        },
    });

    return {
        gutter,
        panel,
        main,
        cell,
    };
});

export const layoutCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const vars = forumLayoutVariables();

    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    cssOut(`.Container`, containerMainStyles() as NestedCSSProperties);

    cssOut(`.Frame-contentWrap`, {
        // ...paddings({
        //     all: globalVars.gutter.half,
        // }),
    });

    cssOut(`.Frame-row`, {
        display: "flex",
        flexWrap: "nowrap",
        ...paddings({
            horizontal: globalVars.gutter.half,
        }),
        $nest: {
            "& > *": {
                ...paddings({
                    horizontal: globalVars.gutter.half,
                }),
            },
        },
    });

    cssOut(`.Panel`, {
        width: unit(vars.panel.paddedWidth),
        ...paddings({
            all: globalVars.gutter.half,
        }),
        $nest: {
            "& > *": {
                ...paddings({
                    horizontal: globalVars.gutter.half,
                }),
            },
        },
    });

    cssOut(`.Content.MainContent`, {
        width: unit(vars.main.width),
        ...paddings({
            all: globalVars.gutter.half,
        }),
    });
};
