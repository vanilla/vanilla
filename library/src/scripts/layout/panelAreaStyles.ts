/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, calc, linearGradient, ColorHelper, translateY } from "csx";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { panelWidgetClasses, panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import { paddings, unit, colorOut, ColorValues } from "@library/styles/styleHelpers";
import { NestedCSSSelectors } from "typestyle/lib/types";

export const panelAreaClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const layoutVars = layoutVariables();
    const vars = layoutVariables();
    const mediaQueries = vars.mediaQueries();
    const style = styleFactory("panelArea");
    const classesPanelWidget = panelWidgetClasses();

    const root = style(
        {
            width: percent(100),
            ...paddings({
                all: globalVars.gutter.half,
            }),
            $nest: {
                "& .heading": {
                    $nest: lineHeightAdjustment(),
                },
                [`&.inheritHeight > .${classesPanelWidget.root}`]: {
                    flexGrow: 1,
                },
                "&.hasNoVerticalPadding": {
                    ...paddings({ vertical: 0 }),
                },
                "&.hasNoHorizontalPadding": {
                    ...paddings({ horizontal: 0 }),
                },
                "&.isSelfPadded": {
                    ...paddings({ all: 0 }),
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                horizontal: 0,
            }),
        }),
    );

    const overflowFull = (offset: number) =>
        style("overflowFull", {
            maxHeight: calc(`100vh - ${unit(offset)}`),
            overflow: "auto",
            position: "relative",
            minHeight: 100,
            paddingBottom: 50,
            paddingTop: 50,
            marginTop: -50,
        });

    const areaOverlay = style("areaOverlay", {
        position: "relative",
    });

    const areaOverlayBefore = (color?: ColorHelper, side?: "left" | "right") => {
        let gradientColor = color ?? globalVars.mainColors.bg;

        return style("areaOverlayBefore", {
            zIndex: 3,
            top: 0,
            left: 0,
            right: 0,
            position: "absolute",
            width: percent(100),
        });
    };
    const areaOverlayAfter = (color?: ColorHelper, side?: "left" | "right") => {
        let gradientColor = color ?? globalVars.mainColors.bg;

        return style("areaOverlayAfter", {
            zIndex: 1,
            bottom: 0,
            left: 0,
            right: 0,
            position: "absolute",
            height: 50,
            background: linearGradient("to bottom", colorOut(gradientColor.fade(0))!, colorOut(gradientColor)!),
            width: percent(100),
        });
    };

    return { root, overflowFull, areaOverlayBefore, areaOverlayAfter, areaOverlay };
});
