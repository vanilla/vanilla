/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { cssRule } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { frameVariables } from "@library/styles/frameStyles";
import { colorOut } from "@library/styles/styleHelpers";

export const radioTabsVariables = useThemeCache(() => {
    const gVars = globalVariables();
    const makeVars = variableFactory("radioTabs");

    const colors = makeVars("colors", {
        bg: gVars.mainColors.bg,
        fg: gVars.mainColors.fg,
        active: {
            border: gVars.mixPrimaryAndBg(0.5),
        },
        hover: {
            bg: gVars.mixPrimaryAndBg(0.1),
            fg: gVars.mainColors.fg,
        },
        selected: {
            bg: gVars.mixBgAndFg(0.2),
            fg: gVars.mainColors.fg,
        },
    });

    return { colors };
});

export const radioTabCss = useThemeCache(() => {
    const vars = radioTabsVariables();

    cssRule(".radioButtonsAsTabs-input + .radioButtonsAsTabs-label", {
        $nest: {
            "&:hover, &:focus": {
                backgroundColor: colorOut(vars.colors.hover.bg),
                color: colorOut(vars.colors.hover.fg),
                borderColor: colorOut(vars.colors.active.border),
            },
        },
    });

    cssRule(".radioButtonsAsTabs-input:checked + .radioButtonsAsTabs-label", {
        backgroundColor: colorOut(vars.colors.selected.bg),
        $nest: {
            "&:hover, &:focus": {
                backgroundColor: colorOut(vars.colors.hover.bg),
                color: colorOut(vars.colors.hover.fg),
            },
        },
    });
});
