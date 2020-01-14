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

export const textLinkCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);
    // Various links
    mixinTextLink(".Navigation-linkContainer a");
    mixinTextLink(".Panel .PanelInThisDiscussion a");
    mixinTextLink(".Panel .Leaderboard a");
    mixinTextLink(".Panel .InThisConversation a");
    mixinTextLink(".FilterMenu a", true);
    mixinTextLink(".Breadcrumbs a", true);
    mixinTextLink("div.Popup .Body a");
    mixinTextLink(".selectBox-toggle");
    mixinTextLink(".followButton");
    mixinTextLink(".QuickSearchButton");
    mixinTextLink(".SelectWrapper::after");
    mixinTextLink(".Back a");
    mixinTextLink(".OptionsLink-Clipboard");
    mixinTextLink("a.OptionsLink");
    mixinTextLink(".MorePager a");
    // Links that have FG color by default but regular state colors.
    mixinTextLink(".ItemContent a", true);
    mixinTextLink(".DataList .Item h3 a", true);
    mixinTextLink(".DataList .Item a.Title", true);
    mixinTextLink(".DataList .Item .Title a", true);
    mixinTextLink("a.Tag", true);
    mixinTextLink(".MenuItems a", true);

    mixinTextLink(".DataTable h2 a", true);
    mixinTextLink(".DataTable h3 a", true);
    mixinTextLink(".DataTable .Title.Title a", true);
};

// Mixins replacement
export const mixinTextLink = (selector: string, skipDefaultColor = false) => {
    const linkColors = setAllLinkColors();
    selector = trimTrailingCommas(selector);

    if (!skipDefaultColor) {
        cssOut(selector, {
            color: linkColors.color,
        });
    }
    nestedWorkaround(selector, linkColors.nested);
};
