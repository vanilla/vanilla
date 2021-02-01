/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export const categoryListVariables = useThemeCache(() => {
    const makeVars = variableFactory("categoryList");

    /**
     * @varGroup categoryListPage.contentBoxes
     * @description Content boxes for the category list page.
     * @expand contentBoxes
     */
    const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    return { contentBoxes };
});
