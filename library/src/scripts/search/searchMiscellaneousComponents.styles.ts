/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { fonts, unit } from "@library/styles/styleHelpers";

export const searchMiscellaneousComponentsVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("searchComponents");
    return {};
});

export const searchMiscellaneousComponentsClasses = useThemeCache(() => {
    const style = styleFactory("searchIn");
    const globalVars = globalVariables();
    const vars = searchMiscellaneousComponentsVariables();

    const sortAndPagination = style("sortAndPagination", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const sort = style("sort", {
        marginRight: unit(globalVars.gutter.size),
    });

    const sortLabel = style("sortLabel", {});

    return {
        sortAndPagination,
        sort,
        sortLabel,
    };
});
