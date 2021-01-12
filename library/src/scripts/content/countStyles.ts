/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const countVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("count");

    const font = themeVars("font", {
        size: 10,
    });

    const sizing = themeVars("sizing", {
        height: globalVars.fonts.size.large,
    });

    const notifications = themeVars("notifications", {
        bg: globalVars.messageColors.deleted.bg,
    });

    return {
        font,
        sizing,
        notifications,
    };
});

export const countClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = countVariables();
    const style = styleFactory("count");
    const fg = ColorsUtils.isLightColor(vars.notifications.bg)
        ? globalVars.elementaryColors.almostBlack
        : globalVars.elementaryColors.white;

    const root = style({
        ...absolutePosition.topRight(4),
        display: "block",
        backgroundColor: ColorsUtils.colorOut(vars.notifications.bg),
        height: styleUnit(vars.sizing.height),
        lineHeight: styleUnit(vars.sizing.height),
        minWidth: styleUnit(vars.sizing.height),
        fontSize: styleUnit(vars.font.size),
        fontWeight: globalVars.fonts.weights.semiBold,
        borderRadius: styleUnit(vars.sizing.height / 2),
        whiteSpace: "nowrap",
        padding: `0 3px`,
    });
    const text = style("text", {
        display: "block",
        textAlign: "center",
        color: ColorsUtils.colorOut(fg),
    });

    return { root, text };
});
