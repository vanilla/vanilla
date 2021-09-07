/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const formTreeVariables = useThemeCache(() => {
    const makeVars = variableFactory("formTree");

    const globalVars = globalVariables();

    const row = makeVars("row", {
        height: 28,
        // Despite the name in compact mode (mobile) we actually get more compact.
        heightInCompact: 44,
        compactBreakpoint: 650,

        // colors
        bg: globalVars.mainColors.bg,
        activeBg: globalVars.mainColors.primary.fade(0.1),
        draggedBg: globalVars.mainColors.bg,
    });

    return { row };
});
