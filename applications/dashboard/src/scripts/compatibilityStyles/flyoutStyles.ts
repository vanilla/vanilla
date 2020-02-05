/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, importantUnit, IStateSelectors, paddings, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { actionMixin } from "@library/flyouts/dropDownStyles";

export const flyoutCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);

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

    // Flip Checkbox in dropdown for consistency with KB

    cssOut(`.selectBox-item .dropdown-menu-link.selectBox-link`, {
        ...paddings({
            left: importantUnit(26),
            right: importantUnit(30),
        }),
    });

    cssOut(`.selectBox-item.isActive .dropdown-menu-link.selectBox-link`, {
        backgroundColor: colorOut(globalVars.mainColors.primary),
        $nest: {
            "& .dropdown-menu-link.selectBox-link": {
                cursor: "pointer",
            },
        },
    });

    cssOut(".selectBox-selectedIcon", {
        left: "auto",
        right: unit(5),
    });
};

function mixinFlyoutItem(selector: string, classBasedStates?: IStateSelectors) {
    selector = trimTrailingCommas(selector);
    const styles = actionMixin(classBasedStates);
    cssOut(selector, styles);
}
