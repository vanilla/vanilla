import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const layoutOptionsClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const legacyOptions = css({
        marginTop: globalVars.spacer.pageComponentCompact,
        "&.disabled .disablable": {
            pointerEvents: "none",
            opacity: 0.5,
        },
    });

    const legacyOptionsTooltip = css({
        filter: "darken(0.5)",
        marginLeft: 8,
    });

    const legacyOptionTitle = css({
        display: "flex",
        alignItems: "center",
        height: 24,
    });

    return { legacyOptions, legacyOptionTitle, legacyOptionsTooltip };
});
