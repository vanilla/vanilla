/*
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { borders, colorOut, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const toastClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("toast");

    const root = () => {
        return style("box", {
            zIndex: 1,
            position: "fixed",
            bottom: 18,
            left: 18,
            display: "inline-block",
            padding: unit(18),
            margin: unit("5px"),
            ...borders(),
            ...shadowHelper().dropDown(),
            background: colorOut(globalVars.mainColors.bg),
        });
    };

    const buttons = () => {
        return style("button", {
            margin: unit("3px"),
        });
    };

    return { root, buttons };
});
