/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";

export const tableVariables = useThemeCache(() => {
    const vars = variableFactory("table");
    const globalVars = globalVariables();

    const spacing = vars("spacing", {
        padding: {
            all: globalVars.gutter.half,
        },
    });

    const separator = vars("separator", {
        fg: globalVars.separator.color,
        width: globalVars.separator.size,
    });

    const head = vars("head", {
        padding: {
            bottom: 8,
            horizontal: globalVars.gutter.half,
            top: 8,
        },
    });

    const columns = vars("column", {
        basic: {
            minWidth: 100,
        },
        lastActive: {
            minWidth: 100,
        },
    });

    return { spacing, separator, head, columns };
});
