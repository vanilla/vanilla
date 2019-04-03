/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { px } from "csx";
import { media } from "typestyle";
import {useThemeCache, variableFactory} from "@library/styles/styleUtils";

export const layoutVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("globalVariables");

    const gutterSize = 24;
    const gutter = makeThemeVars("gutter",{
        size: gutterSize,
        halfSize: gutterSize / 2,
        quarterSize: gutterSize / 4,
    });

    const panelWidth = 216;
    const panelPaddedWidth = panelWidth + gutterSize * 2;
    const panel = makeThemeVars("panel",{
        width: 216,
        paddedWidth: panelPaddedWidth,
    });

    const middleColumnWidth = 672;
    const middleColumn = makeThemeVars("middleColumn",{
        width: middleColumnWidth,
        paddedWidth: middleColumnWidth + gutterSize,
    });

    const globalContentWidth = (middleColumnWidth + gutterSize) * 2 + gutterSize * 3;
    const mediumWidth = 900;
    const contentSizes = makeThemeVars("content",{
        full: globalContentWidth,
        widgets: mediumWidth < globalContentWidth ? mediumWidth : globalContentWidth,
    });

    const twoColumnBreak = 1200;
    const panelLayoutBreakPoints = makeThemeVars("panelLayoutBreakPoints",{
        noBleed: globalContentWidth,
        twoColumn: twoColumnBreak,
        oneColumn: twoColumnBreak - panelPaddedWidth,
        xs: 500,
    });

    const globalBreakPoints = makeThemeVars("globalBreakPoints",{
        twoColumn: 1200,
        oneColumn: 500,
    });

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

    return {
        gutterSize,
        gutter,
        panelWidth,
        panel,
        middleColumnWidth,
        middleColumn,
        contentSizes,
        globalBreakPoints,
        mediaQueries,
    };
});

