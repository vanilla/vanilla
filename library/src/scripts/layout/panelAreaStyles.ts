/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, calc, linearGradient } from "csx";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";
import { paddings, unit, colorOut } from "@library/styles/styleHelpers";
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

    const overflowHalf = (offset: number) =>
        style("overflowHalf", {
            maxHeight: calc(`50vh - ${unit(globalVars.gutter.size)}`),
            overflow: "auto",
        });
    const overflowFull = (offset: number) =>
        style("overflowFull", {
            maxHeight: calc(`100vh - ${unit(offset)}`),
            overflow: "auto",
            position: "relative",
            paddingBottom: 50,
            paddingTop: 50,
            marginTop: -50,
        });

    const areaOverlay = style("areaOverlay", {});

    const areaOverlayBefore = style("areaOverlayBefore", {
        zIndex: 3,
        top: 0,
        left: 0,
        right: 0,
        position: "absolute",
        height: 50,
        marginTop: -50,
        background: linearGradient(
            "to top",
            colorOut(globalVars.mainColors.bg.fade(0))!,
            colorOut(globalVars.mainColors.bg)!,
        ),
    });
    const areaOverlayAfter = style("areaOverlayAfter", {
        zIndex: 1,
        bottom: 0,
        left: 0,
        right: 0,
        position: "absolute",
        height: 50,
        background: linearGradient(
            "to bottom",
            colorOut(globalVars.mainColors.bg.fade(0))!,
            colorOut(globalVars.mainColors.bg)!,
        ),
    });

    return { root, overflowHalf, overflowFull, areaOverlayBefore, areaOverlayAfter, areaOverlay };
});
