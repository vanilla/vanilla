/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { borders, paddings, unit } from "@library/styles/styleHelpers";

export const rolesClasses = useThemeCache(() => {
    const style = styleFactory("roles");
    const globalVars = globalVariables();
    const metaVars = globalVars.meta;

    const role = style({
        ...borders({ color: metaVars.text.color, radius: 3 }),
        ...paddings({
            horizontal: 4,
        }),
        $nest: {
            "&&": {
                fontSize: metaVars.text.fontSize,
                lineHeight: globalVars.lineHeights.condensed,
            },
        },
    });

    return { role };
});
