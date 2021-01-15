/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";

export const searchMiscellaneousComponentsVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("searchComponents");
    const spacing = makeThemeVars("spacing", {
        margin: 12,
    });
    return {
        spacing,
    };
});

export const searchMiscellaneousComponentsClasses = useThemeCache(() => {
    const style = styleFactory("searchMiscellaneousComponents");
    const globalVars = globalVariables();
    const vars = searchMiscellaneousComponentsVariables();

    const root = style("root", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        marginBottom: styleUnit(vars.spacing.margin),
    });

    const sort = style("sort", {
        display: "flex",
        ...Mixins.margin({
            all: 0,
            right: globalVars.gutter.size,
        }),
        flexGrow: 1,
    });

    const sortLabel = style("sortLabel", {
        alignSelf: "center",
        marginRight: styleUnit(6),
        ...Mixins.font({
            color: globalVars.meta.text.color,
            weight: globalVars.fonts.weights.normal,
        }),
    });

    return {
        root,
        sort,
        sortLabel,
    };
});
