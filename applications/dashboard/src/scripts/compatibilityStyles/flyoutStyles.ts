/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssRaw } from "typestyle";
import {
    borders,
    colorOut,
    IActionStates,
    IStateSelectors,
    negative,
    pointerEvents,
    setAllLinkColors,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

export const flyoutCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    // Dropdown hover/focus colors:
    mixinFlyoutItem(".MenuItems .Item a");
    mixinFlyoutItem(".MenuItems.MenuItems li a");
    mixinFlyoutItem(".Flyout.Flyout li a");
    mixinFlyoutItem(".editor-action.editor-action.editor-action a");
    mixinFlyoutItem("div.token-input-dropdown ul li", { hover: ".token-input-selected-dropdown-item" });

    mixinFlyoutItem("div.token-input-dropdown ul li.token-input-dropdown-item");
    mixinFlyoutItem("div.token-input-dropdown ul li.token-input-dropdown-item2");

    cssOut(
        `
        .Flyout.Flyout,
        .richEditorFlyout,
        .MenuItems
        `,
        {
            color: fg,
            background: bg,
        },
    );
};

function mixinFlyoutItem(selector: string, classBasedStates?: IStateSelectors) {
    selector = trimTrailingCommas(selector);
    cssOut(selector, dropDownClasses().actionMixin(classBasedStates));
}
