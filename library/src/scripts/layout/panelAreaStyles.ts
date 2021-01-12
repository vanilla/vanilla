/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, calc, linearGradient, ColorHelper } from "csx";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { Mixins } from "@library/styles/Mixins";

export const panelAreaClasses = useThemeCache((mediaQueries) => {
    const globalVars = globalVariables();
    const style = styleFactory("panelArea");
    const classesPanelWidget = panelWidgetClasses(mediaQueries);

    const root = style({
        width: percent(100),
        ...Mixins.padding({
            horizontal: globalVariables().widget.padding,
            vertical: globalVariables().widget.padding / 5,
        }),
        ...{
            ".heading": {
                ...lineHeightAdjustment(),
            },
            [`&.inheritHeight > .${classesPanelWidget.root}`]: {
                flexGrow: 1,
            },
            "&.hasNoVerticalPadding": {
                ...Mixins.padding({ vertical: 0 }),
            },
            "&.hasNoHorizontalPadding": {
                ...Mixins.padding({ horizontal: 0 }),
            },
            "&.isSelfPadded": {
                ...Mixins.padding({ all: 0 }),
            },
            ...mediaQueries({
                [LayoutTypes.TWO_COLUMNS]: {
                    oneColumnDown: {
                        ...Mixins.padding({
                            horizontal: 0,
                        }),
                    },
                },
                [LayoutTypes.THREE_COLUMNS]: {
                    oneColumnDown: {
                        ...Mixins.padding({
                            horizontal: 0,
                        }),
                    },
                },
            }),
        },
    });

    const overflowFull = useThemeCache((offset: number) =>
        style("overflowFull", {
            height: calc(`100vh - ${styleUnit(offset)}`),
            overflow: "auto",
            position: "relative",
            minHeight: 100,
            paddingBottom: 50,
            paddingTop: 50,
            marginTop: -50,
        }),
    );

    const areaOverlay = style("areaOverlay", {
        position: "relative",
    });

    const areaOverlayBefore = useThemeCache((color?: ColorHelper, side?: "left" | "right") => {
        let gradientColor = color ?? globalVars.mainColors.bg;

        return style("areaOverlayBefore", {
            zIndex: 3,
            top: 0,
            left: 0,
            right: 0,
            position: "absolute",
            height: 50,
            background: linearGradient(
                "to top",
                ColorsUtils.colorOut(gradientColor.fade(0))!,
                ColorsUtils.colorOut(gradientColor)!,
            ),
            width: percent(100),
        });
    });

    const areaOverlayAfter = useThemeCache((color?: ColorHelper, side?: "left" | "right") => {
        let gradientColor = color ?? globalVars.mainColors.bg;

        return style("areaOverlayAfter", {
            zIndex: 1,
            bottom: 0,
            left: 0,
            right: 0,
            position: "absolute",
            height: 50,
            background: linearGradient(
                "to bottom",
                ColorsUtils.colorOut(gradientColor.fade(0))!,
                ColorsUtils.colorOut(gradientColor)!,
            ),
            width: percent(100),
        });
    });

    return {
        root: root + " panelArea",
        overflowFull,
        areaOverlayBefore,
        areaOverlayAfter,
        areaOverlay,
    };
});
