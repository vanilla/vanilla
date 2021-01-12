/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";

export const rolesClasses = useThemeCache(() => {
    const style = styleFactory("roles");
    const globalVars = globalVariables();
    const metaVars = globalVars.meta;

    const role = style({
        ...Mixins.border({ color: metaVars.text.color, radius: 3 }),
        ...Mixins.padding({
            horizontal: 4,
        }),
        ...{
            "&&": {
                fontSize: metaVars.text.size,
                lineHeight: globalVars.lineHeights.condensed,
            },
        },
    });

    return { role };
});
