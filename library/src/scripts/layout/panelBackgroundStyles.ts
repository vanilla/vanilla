/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { calc, color, percent, px } from "csx";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import { useLayout } from "@library/layout/LayoutContext";

export const panelBackgroundVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("panelBackground");

    const config = makeThemeVars("config", {
        render: false,
    });

    const colors = makeThemeVars("colors", {
        backgroundColor: color("#f4f6f9"),
    });

    return { config, colors };
});

export const panelBackgroundClasses = useThemeCache(() => {
    const style = styleFactory("panelBackground");
    const vars = panelBackgroundVariables();
    const widgetVars = panelWidgetVariables();
    const globalVars = globalVariables();
    const { currentLayoutVariables } = useLayout();

    const root = style({
        position: "absolute",
        left: 0,
        height: percent(100),
        width: calc(
            `50% - ${unit(currentLayoutVariables.middleColumn.paddedWidth() / 2 + globalVars.gutter.size * 2 - 20)}`,
        ),
        minWidth: unit(
            currentLayoutVariables.panel.paddedWidth +
                currentLayoutVariables.gutter.full -
                currentLayoutVariables.panelLayoutSpacing.withPanelBackground.gutter,
        ),
        backgroundColor: colorOut(vars.colors.backgroundColor),
        zIndex: 0,
    });

    const backgroundColor = style("hasBackgroundColor", {
        $nest: {
            "&&": {
                backgroundColor: colorOut(panelBackgroundVariables().colors.backgroundColor),
            },
        },
    });

    return {
        root,
        backgroundColor,
    };
});
