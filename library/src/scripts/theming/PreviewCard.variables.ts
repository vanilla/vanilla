/*
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, rgba, px } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

const previewCardVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("previewCard");

    const colors = makeThemeVars("colors", {
        fg: color("#adb2bb"),
        white: color("#ffffff"),
        imgColor: color("#0291db"),
        btnTextColor: color("#555a62"),
        overlayBg: rgba(0, 0, 0, 0.4),
    });

    const container = makeThemeVars("container", {
        maxWidth: 600,
        minWidth: 220,
        ratioHeight: 225,
        ratioWidth: 310,
    });

    const menuBar = makeThemeVars("menuBar", {
        height: 10,
        padding: {
            top: 0,
            horizontal: 10,
        },
        dotSize: 4,
    });

    const actionDropdown = makeThemeVars("actionDropdown", {
        state: {
            bg: ColorsUtils.offsetLightness(colors.overlayBg, 0.04),
        },
    });

    return {
        colors,
        container,
        menuBar,
        actionDropdown,
    };
});

export default previewCardVariables;
