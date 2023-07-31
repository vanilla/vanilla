import { useThemeCache } from "@library/styles/themeCache";
import { variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export default useThemeCache(() => {
    const globalVars = globalVariables();

    const makeThemeVars = variableFactory("floatingToolbar"); //fixme: use sensible namespace

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    const nub = makeThemeVars("nub", {
        width: 12,
    });

    const menu = makeThemeVars("menu", {
        borderWidth: 1,
        offset: nub.width * 2,
    });

    const scrollContainer = makeThemeVars("scrollContainer", {
        overshoot: 48,
    });

    return { colors, nub, menu, scrollContainer };
});
