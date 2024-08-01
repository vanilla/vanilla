import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { color } from "csx";

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
