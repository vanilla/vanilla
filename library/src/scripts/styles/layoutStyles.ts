/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { px } from "csx";
import { media } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, componentThemeVariables } from "@library/styles/styleHelpers";

export const layoutVariables = (theme?: object) => {
    const themeVars = componentThemeVariables(theme, "globalVariables");

    const gutterSize = 24;
    const gutter = {
        size: px(gutterSize),
        halfSize: px(gutterSize / 2),
        quarterSize: px(gutterSize / 4),
        ...themeVars.subComponentStyles("gutter"),
    };

    const panelWidth = 216;
    const panelPaddedWidth = panelWidth + gutterSize * 2;
    const panel = {
        width: px(216),
        paddedWidth: panelPaddedWidth,
        ...themeVars.subComponentStyles("panel"),
    };

    const middleColumnWidth = 672;
    const middleColumn = {
        width: px(middleColumnWidth),
        paddedWidth: px(middleColumnWidth + gutterSize),
        ...themeVars.subComponentStyles("middleColumn"),
    };

    const globalContentWidth = (middleColumnWidth + gutterSize) * 2 + gutterSize * 3;
    const content = {
        width: px(globalContentWidth),
        ...themeVars.subComponentStyles("content"),
    };

    const twoColumnBreak = 1200;
    const panelLayoutBreakPoints = {
        noBleed: globalContentWidth,
        twoColumn: twoColumnBreak,
        oneColumn: twoColumnBreak - panelPaddedWidth,
        xs: 500,
        ...themeVars.subComponentStyles("panelLayoutBreakPoints"),
    };

    const globalBreakPoints = {
        twoColumn: px(1200),
        oneColumn: px(500),
        ...themeVars.subComponentStyles("globalBreakPoints"),
    };

    const mediaQueries = () => {
        const noBleed = styles => {
            return media({ maxWidth: px(panelLayoutBreakPoints.noBleed) }, styles);
        };

        const twoColumns = styles => {
            return media({ maxWidth: px(panelLayoutBreakPoints.twoColumn) }, styles);
        };

        const oneColumn = styles => {
            return media({ maxWidth: px(panelLayoutBreakPoints.oneColumn) }, styles);
        };

        const xs = styles => {
            return media({ maxWidth: px(panelLayoutBreakPoints.xs) }, styles);
        };

        return { noBleed, twoColumns, oneColumn, xs };
    };

    return { gutterSize, gutter, panelWidth, panel, middleColumnWidth, middleColumn, content, mediaQueries };
};
