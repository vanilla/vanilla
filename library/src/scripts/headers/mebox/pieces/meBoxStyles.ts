/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, flexHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const meBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formVars = formElementsVariables();
    const titleBarVars = titleBarVariables();
    const debug = debugHelper("meBox");
    const mediaQueries = layoutVariables().mediaQueries();
    const flex = flexHelper();
    const style = styleFactory("meBox");

    const root = style(
        {
            ...debug.name(),
            display: "flex",
            alignItems: "center",
            height: styleUnit(titleBarVars.sizing.height),
        },
        mediaQueries.oneColumnDown({
            height: styleUnit(titleBarVars.sizing.mobile.height),
        }),
    );

    const buttonContent = style("buttonContent", {
        ...flex.middle(),
        width: styleUnit(formVars.sizing.height),
        maxWidth: styleUnit(formVars.sizing.height),
        flexBasis: styleUnit(formVars.sizing.height),
        height: styleUnit(titleBarVars.meBox.sizing.buttonContents),
        borderRadius: styleUnit(globalVars.border.radius),
    });

    return {
        root,
        buttonContent,
    };
});
