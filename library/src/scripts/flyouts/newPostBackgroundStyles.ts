import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit, colorOut, absolutePosition, negativeUnit } from "@library/styles/styleHelpers";

export const newPostBackgroundVariables = useThemeCache(() => {
    const themeVars = variableFactory("newPostBackground");

    const container = themeVars("container", {
        top: 0,
        left: 0,
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
        zIndex: "9999",
        top: unit(vars.container.top),
        left: unit(vars.container.left),
    });

    return {
        container,
    };
});
