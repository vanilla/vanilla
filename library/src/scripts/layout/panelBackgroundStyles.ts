/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { calc, percent } from "csx";
import { panelBackgroundVariables } from "./PanelBackground.variables";

export const panelBackgroundClasses = useThemeCache(() => {
    const style = styleFactory("panelBackground");

    const vars = panelBackgroundVariables();
    const layoutVars = layoutVariables();
    const globalVars = globalVariables();

    const root = style({
        position: "absolute",
        left: 0,
        height: percent(100),

        width: calc(`50% - ${styleUnit(layoutVars.middleColumn.paddedWidth / 2 + globalVars.gutter.size * 2 - 20)}`),
        minWidth: styleUnit(
            layoutVars.panel.paddedWidth +
                layoutVars.gutter.full -
                layoutVars.panelLayoutSpacing.withPanelBackground.gutter,
        ),
        backgroundColor: ColorsUtils.colorOut(vars.colors.backgroundColor),
        zIndex: 0,
    });

    const backgroundColor = style("hasBackgroundColor", {
        ...{
            "&&": {
                backgroundColor: ColorsUtils.colorOut(panelBackgroundVariables().colors.backgroundColor),
            },
        },
    });

    return {
        root,
        backgroundColor,
    };
});
