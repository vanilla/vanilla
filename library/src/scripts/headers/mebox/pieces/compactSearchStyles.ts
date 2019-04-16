/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, px } from "csx";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { searchBarClasses } from "@library/features/search/searchBarStyles";

export const compactSearchVariables = useThemeCache(() => {
    const makeVars = variableFactory("compactSearch");
    const vanillaHeaderVars = vanillaHeaderVariables();
    const globalVars = globalVariables();

    const baseColor = vanillaHeaderVars.colors.bg.darken(0.05);
    const colors = makeVars("colors", {
        bg: baseColor.fade(0.8),
        fg: vanillaHeaderVars.colors.fg,
        placeholder: globalVars.mainColors.bg,
        active: {
            bg: baseColor,
        },
    });

    return { colors };
});
export const compactSearchClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementsVars = formElementsVariables();
    const vars = compactSearchVariables();
    const style = styleFactory("compactSearch");

    const root = style({
        $nest: {
            ".searchBar": {
                flexGrow: 1,
            },
            "& .searchBar__input": {
                color: colorOut(vars.colors.fg),
            },
            ".searchBar-valueContainer": {
                height: unit(formElementsVars.sizing.height),
                backgroundColor: colorOut(vars.colors.bg),
                border: 0,
            },
            ".hasFocus .searchBar-valueContainer": {
                backgroundColor: colorOut(vars.colors.active.bg),
            },
            ".searchBar__placeholder": {
                color: colorOut(vars.colors.placeholder),
            },
            ".searchBar-icon": {
                color: colorOut(vars.colors.placeholder),
            },
            "&.isOpen": {
                width: percent(100),
            },
            "&.isCentered": {
                margin: "auto",
            },
            ".suggestedTextInput-inputText": {
                borderTopRightRadius: unit(globalVars.border.radius),
                borderBottomRightRadius: unit(globalVars.border.radius),
            },
        },
    });

    const contents = style("contents", {
        display: "flex",
        alignItems: "center",
        flexWrap: "nowrap",
        $nest: {
            ["& ." + searchBarClasses().content]: {
                minHeight: "initial",
            },
        },
    });

    const close = style("close", {
        color: "inherit",
        whiteSpace: "nowrap",
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const cancelContents = style("cancelContents", {
        padding: px(4),
    });
    return { root, contents, close, cancelContents };
});
