/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";

export const searchInFilterClasses = useThemeCache(() => {
    const style = styleFactory("searchIn");
    const vars = globalVariables();

    const separator = style("separator", {});

    return {
        separator,
    };
});
