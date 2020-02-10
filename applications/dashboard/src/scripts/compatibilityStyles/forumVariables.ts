import { useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const forumGlobalVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumGlobals");

    const multiButton = makeThemeVars("multiButton", {
        handle: {
            bg: {},
            state: {},
        },
    });
});
