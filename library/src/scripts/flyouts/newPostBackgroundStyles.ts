/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";

export const newPostBackgroundVariables = useThemeCache(() => {
    const themeVars = variableFactory("newPostBackground");
    const globalVars = globalVariables();

    const container = themeVars("container", {
        top: 0,
        left: 0,
        color: {
            open: globalVars.elementaryColors.black.fade(0.4),
            close: globalVars.mainColors.bg,
        },
        duration: 300,
    });

    return {
        container,
    };
});

export const newPostBackgroundClasses = useThemeCache(() => {
    const style = styleFactory("newPostBackground");
    const vars = newPostBackgroundVariables();

    const container = style("container", {
        height: "100vh",
        width: "100vw",
        position: "absolute",
        top: styleUnit(vars.container.top),
        left: styleUnit(vars.container.left),
        zIndex: 9999,
    });

    return {
        container,
    };
});
