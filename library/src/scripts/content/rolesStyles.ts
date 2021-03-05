/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { metasVariables } from "@library/metas/Metas.variables";

export const rolesClasses = useThemeCache(() => {
    const style = styleFactory("roles");
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const role = style({
        ...Mixins.border({ color: metasVars.font.color, radius: 3 }),
        ...Mixins.padding({
            horizontal: 4,
        }),
        ...{
            "&&": {
                fontSize: metasVars.font.size,
                lineHeight: globalVars.lineHeights.condensed,
            },
        },
    });

    return { role };
});
