/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { borders } from "@library/styles/styleHelpers";

export const rolesClasses = useThemeCache(() => {
    const style = styleFactory("roles");
    const vars = globalVariables().meta;

    const role = style({
        ...borders({ color: vars.text.color, radius: 3 }),
    });

    return { role };
});
