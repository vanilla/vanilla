/*
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { buttonGlobalVariables } from "@library/forms/Button.variables";

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
            ...Mixins.padding({
                horizontal: globalVars.gutter.size,
                top: globalVars.gutter.size,
                bottom: globalVars.gutter.half,
            }),
            margin: styleUnit(globalVars.gutter.quarter),
            ...Mixins.border(),
            ...shadowHelper().dropDown(),
            background: ColorsUtils.colorOut(globalVars.mainColors.bg),
            p: {
                margin: 0,
            },
        });
    };

    const buttons = style("button", {
        minHeight: buttonGlobalVariables().sizing.minHeight,
        ...flexHelper().middleLeft(),
    });

    const button = style("button", {
        margin: styleUnit("3px"),
        fontSize: globalVars.fonts.size.medium,
    });

    return { root, buttons, button };
});
