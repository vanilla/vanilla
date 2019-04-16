/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings } from "@library/styles/styleHelpers";

export const panelLayoutClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("panelLayout");

    const panel = style({
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        ...paddings({
            horizontal: globalVars.gutter.half,
        }),
    });

    return { panel };
});
