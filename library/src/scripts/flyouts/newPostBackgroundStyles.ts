import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit, colorOut, absolutePosition, negativeUnit } from "@library/styles/styleHelpers";

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
        top: unit(vars.container.top),
        left: unit(vars.container.left),
        zIndex: 9999,
    });

    return {
        container,
    };
});
