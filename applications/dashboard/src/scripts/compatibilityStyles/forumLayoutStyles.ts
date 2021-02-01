/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { containerMainMediaQueries, containerMainStyles } from "@library/layout/components/containerStyles";
import { CSSObject } from "@emotion/css";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { media } from "@library/styles/styleShim";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { Mixins } from "@library/styles/Mixins";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const forumLayoutVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("forumLayout");

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter: globalVars.constants.fullGutter,
        panelWidth: 220, // main calculated based on panel width
        breakPoints: {
            // Other break points are calculated
            oneColumn: 1200,
            tablet: 991,
            mobile: 768,
            xs: 576,
        },
    });

    const mediaQueries = () => {
        const noBleed = (styles: CSSObject, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: panel.paddedWidth,
                    minWidth: useMinWidth ? foundationalWidths.breakPoints.oneColumn + 1 : undefined,
                },
                styles,
            );
        };

        const noBleedDown = (styles: CSSObject) => {
            return noBleed(styles, false);
        };

        const oneColumn = (styles: CSSObject, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: foundationalWidths.breakPoints.oneColumn,
                    minWidth: useMinWidth ? foundationalWidths.breakPoints.tablet + 1 : undefined,
                },
                styles,
            );
        };

        const oneColumnDown = (styles: CSSObject) => {
            return oneColumn(styles, false);
        };

        const tablet = (styles: CSSObject, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: foundationalWidths.breakPoints.tablet,
                    minWidth: useMinWidth ? foundationalWidths.breakPoints.mobile + 1 : undefined,
                },
                styles,
            );
        };

        const tabletDown = (styles: CSSObject) => {
            return tablet(styles, false);
        };

        const mobile = (styles: CSSObject, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: foundationalWidths.breakPoints.mobile,
                    minWidth: useMinWidth ? foundationalWidths.breakPoints.xs + 1 : undefined,
                },
                styles,
            );
        };

        const aboveMobile = (styles: CSSObject) => {
            return media(
                {
                    minWidth: foundationalWidths.breakPoints.mobile + 1,
                },
                styles,
            );
        };

        const mobileDown = (styles: CSSObject) => {
            return mobile(styles, false);
        };

        const xs = (styles: CSSObject) => {
            return media(
                {
                    maxWidth: foundationalWidths.breakPoints.xs,
                },
                styles,
            );
        };

        const aboveXs = (styles: CSSObject) => {
            return media(
                {
                    minWidth: foundationalWidths.breakPoints.xs + 1,
                },
                styles,
            );
        };

        return {
            noBleed,
            noBleedDown,
            oneColumn,
            oneColumnDown,
            tablet,
            tabletDown,
            aboveMobile,
            mobile,
            mobileDown,
            aboveXs,
            xs,
        };
    };

    const gutter = makeThemeVars("gutter", {
        full: foundationalWidths.fullGutter, // 48
        size: foundationalWidths.fullGutter / 2, // 24
        halfSize: foundationalWidths.fullGutter / 4, // 12
        quarterSize: foundationalWidths.fullGutter / 8, // 6
        mainGutterOffset: foundationalWidths.fullGutter,
    });

    const panel = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
        paddedWidth: foundationalWidths.panelWidth + gutter.size,
    });

    const main = makeThemeVars("main", {
        width: calc(`100% - ${styleUnit(panel.paddedWidth + gutter.mainGutterOffset)}`),
        topSpacing: 40,
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
        mediaQueries,
    };
});

export const forumLayoutCSS = () => {
    const globalVars = globalVariables();
    const vars = forumLayoutVariables();

    const mainColors = globalVars.mainColors;
    const mediaQueries = vars.mediaQueries();

    cssOut(
        `.Container, body.Section-Event.NoPanel .Frame-content > .Container`,
        mediaQueries.tablet({
            ...Mixins.padding({
                horizontal: 12,
            }),
        }),
    );

    cssOut(`body.Section-Event.NoPanel .Frame-content > .Container`, containerMainStyles());

    cssOut(`.Frame-content .HomepageTitle`, {
        ...lineHeightAdjustment(),
    });

    cssOut(
        `.Panel`,
        {
            width: styleUnit(vars.panel.paddedWidth),
            ...Mixins.padding({
                vertical: globalVars.gutter.half,
            }),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    cssOut(
        `.Content.MainContent`,
        {
            width: styleUnit(vars.main.width),
            ...Mixins.padding({
                all: globalVars.gutter.half,
            }),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    cssOut(`.Container`, containerMainStyles(), containerMainMediaQueries());

    cssOut(`.Frame-row`, {
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "space-between",
        ...Mixins.padding({
            all: globalVars.gutter.half,
        }),
        "& > *": {
            ...Mixins.padding({
                horizontal: globalVars.gutter.half,
            }),
        },
        ...mediaQueries.oneColumnDown({
            flexWrap: important("wrap"),
            ...Mixins.padding({
                horizontal: 0,
            }),
        }),
        ...mediaQueries.tablet({
            ...Mixins.padding({
                horizontal: 0,
            }),
        }),
    });
};
