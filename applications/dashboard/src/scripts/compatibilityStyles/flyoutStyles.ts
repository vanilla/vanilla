/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantUnit, IStateSelectors } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { actionMixin, dropDownVariables } from "@library/flyouts/dropDownStyles";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";

export const flyoutCSS = () => {
    const globalVars = globalVariables();
    const dropdownVars = dropDownVariables();
    const mainColors = globalVars.mainColors;
    const fg = ColorsUtils.colorOut(mainColors.fg);
    const bg = ColorsUtils.colorOut(mainColors.bg);

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
            ...Mixins.font({
                size: globalVars.fonts.size.medium,
            }),
        },
    );

    // Flip Checkbox in dropdown for consistency with KB

    cssOut(`.selectBox-item .dropdown-menu-link.selectBox-link`, {
        ...Mixins.padding({
            left: importantUnit(26),
            right: importantUnit(38),
        }),
    });

    cssOut(`.selectBox-item.isActive .dropdown-menu-link.selectBox-link`, {
        backgroundColor: ColorsUtils.colorOut(globalVars.states.active.highlight),
        ...{
            ".dropdown-menu-link.selectBox-link": {
                cursor: "pointer",
            },
        },
    });

    cssOut(".selectBox-selectedIcon", {
        left: "auto",
        right: styleUnit(13),
        color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
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
            borderBottomColor: ColorsUtils.colorOut(globalVars.separator.color),
        },
    );

    cssOut(".MenuItems, .Flyout.Flyout", {
        ...Mixins.border(globalVars.borderType.dropDowns),
        ...shadowOrBorderBasedOnLightness(
            dropdownVars.contents.bg,
            Mixins.border(dropdownVars.contents.border),
            shadowHelper().dropDown(),
        ),
        overflow: "hidden",
    });
};

function mixinFlyoutItem(selector: string, classBasedStates?: IStateSelectors) {
    selector = trimTrailingCommas(selector);
    const styles = actionMixin(classBasedStates);
    cssOut(selector, styles);
}
