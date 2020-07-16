/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, fonts, importantUnit, IStateSelectors, paddings, unit } from "@library/styles/styleHelpers";
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
            background: bg,
            ...fonts({
                size: globalVars.fonts.size.medium,
            }),
        },
    );

    // Flip Checkbox in dropdown for consistency with KB

    cssOut(`.selectBox-item .dropdown-menu-link.selectBox-link`, {
        ...paddings({
            left: importantUnit(26),
            right: importantUnit(38),
        }),
    });

    cssOut(`.selectBox-item.isActive .dropdown-menu-link.selectBox-link`, {
        backgroundColor: colorOut(globalVars.states.active.highlight),
        $nest: {
            "& .dropdown-menu-link.selectBox-link": {
                cursor: "pointer",
            },
        },
    });

    cssOut(".selectBox-selectedIcon", {
        left: "auto",
        right: unit(13),
        color: colorOut(globalVars.mainColors.primaryContrast),
    });

    cssOut(
        `
        .MenuItems hr,
        .MenuItems .menu-separator,
        .MenuItems .dd-separator,
        .MenuItems .editor-action-separator,
        .Flyout.Flyout hr,
        .Flyout.Flyout .menu-separator,
        .Flyout.Flyout .dd-separator,
        .Flyout.Flyout .editor-action-separator
        `,
        {
            borderBottomColor: colorOut(globalVars.separator.color),
        },
    );
};

function mixinFlyoutItem(selector: string, classBasedStates?: IStateSelectors) {
    selector = trimTrailingCommas(selector);
    const styles = actionMixin(classBasedStates);
    cssOut(selector, styles);
}
