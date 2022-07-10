/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, flexHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const meBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formVars = formElementsVariables();
    const titleBarVars = titleBarVariables();
    const debug = debugHelper("meBox");
    const mediaQueries = oneColumnVariables().mediaQueries();
    const flex = flexHelper();

    const root = css(
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

    const buttonContent = css({
        ...flex.middle(),
        width: styleUnit(formVars.sizing.height),
        maxWidth: styleUnit(formVars.sizing.height),
        flexBasis: styleUnit(formVars.sizing.height),
        height: styleUnit(titleBarVars.meBox.sizing.buttonContents),
        borderRadius: styleUnit(globalVars.border.radius),
    });

    const meboxItem = css({
        display: "flex",
        alignItems: "center",
        flexDirection: "column",
    });

    const label = css({
        ...Mixins.font({ ...titleBarVars.meBox.label.font }),
        ...Mixins.margin({ ...titleBarVars.meBox.label.spacing }),
        whiteSpace: "nowrap",
    });

    return {
        root,
        buttonContent,
        meboxItem,
        label,
    };
});
