/*
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { borders, colorOut, unit, flexHelper, paddings } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { buttonGlobalVariables } from "@library/forms/buttonStyles";

export const toastClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("toast");

    const root = () => {
        return style("box", {
            fontSize: globalVars.fonts.size.medium,
            zIndex: 1,
            position: "fixed",
            bottom: 18,
            left: 18,
            display: "inline-block",
            ...paddings({
                horizontal: globalVars.gutter.size,
                top: globalVars.gutter.size,
                bottom: globalVars.gutter.half,
            }),
            margin: unit(globalVars.gutter.quarter),
            ...borders(),
            ...shadowHelper().dropDown(),
            background: colorOut(globalVars.mainColors.bg),
            $nest: {
                p: {
                    margin: 0,
                },
            },
        });
    };

    const buttons = style("button", {
        minHeight: buttonGlobalVariables().sizing.minHeight,
        ...flexHelper().middleLeft(),
    });

    const button = style("button", {
        margin: unit("3px"),
        fontSize: globalVars.fonts.size.medium,
    });

    return { root, buttons, button };
});
