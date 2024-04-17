/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

const voteCounterVariables = useThemeCache(() => {
    const globalVars = globalVariables();

    const makeThemeVars = variableFactory("voteCounter");

    const colors = makeThemeVars("colors", {
        bg: "#e8e8e8",
        fg: globalVars.elementaryColors.darkText,
    });

    const sizing = {
        height: 32,
        width: 32,
        magicOffset: 16,
    };

    return {
        colors,
        sizing,
    };
});

export default voteCounterVariables;
