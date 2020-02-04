/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssRaw } from "typestyle";
import {
    absolutePosition,
    borders,
    buttonStates,
    colorOut,
    IActionStates,
    importantUnit,
    IStateSelectors,
    negative,
    paddings,
    pointerEvents,
    setAllLinkColors,
    textInputSizingFromFixedHeight,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { actionMixin, dropDownClasses, dropDownVariables } from "@library/flyouts/dropDownStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { buttonResetMixin } from "@library/forms/buttonStyles";

export const groupsCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    cssOut(`.groupToolbar`, {
        marginTop: unit(32),
    });

    cssOut(
        `
        .ButtonGroup.Open .Button.GroupOptionsTitle::before,
        .Button.GroupOptionsTitle::before,
        .Button.GroupOptionsTitle:active::before,
        .Button.GroupOptionsTitle:focus::before
        `,
        {
            color: "inherit",
            marginRight: unit(6),
        },
    );

    cssOut(`.Group-Header.NoBanner .Group-Header-Info`, {
        paddingLeft: unit(0),
    });

    cssOut(`.Group-Header.NoBanner .Group-Icon-Big-Wrap`, {
        position: "relative",
    });

    cssOut(`.Group-Header`, {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
    });

    cssOut(`a.ChangePicture`, {
        ...absolutePosition.fullSizeOfParent(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        opacity: 0,
    });

    cssOut(`.Group-Banner`, {
        ...absolutePosition.fullSizeOfParent(),
    });
};
