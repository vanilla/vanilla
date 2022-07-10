/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const embedButtonClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("embedButton");

    const button = style("button", {
        width: 36,
        height: 36,
        display: "flex",
        alignItems: "center",
        border: 0,
        background: "none",
        cursor: "pointer",
        borderRadius: globalVars.border.radius,
        ...{
            "&:hover, &:focus": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&.isActive": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                background: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.1)),
            },
        },
    });

    return { button };
});
