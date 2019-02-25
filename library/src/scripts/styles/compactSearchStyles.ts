/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { unit, debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";
import { percent, px } from "csx";

export function compactSearchClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const vanillaHeaderVars = vanillaHeaderVariables(theme);
    const debug = debugHelper("compactSearch");

    const root = style({
        ...debug.name(),
        $nest: {
            ".searchBar": {
                flexGrow: 1,
            },
            ".searchBar-valueContainer.suggestedTextInput-inputText": {
                height: unit(formElementVars.sizing.height),
                backgroundColor: vanillaHeaderVars.colors.fg.fade(0.15).toString(),
                border: 0,
            },
            ".searchBar__placeholder": {
                color: globalVars.mainColors.bg.toString(),
            },
            ".searchBar__control": {
                opacity: 0.8,
                $nest: {
                    "&.searchBar__control--isFocused": {
                        opacity: 1,
                    },
                },
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

    const contents = style({
        display: "flex",
        alignItems: "center",
        flexWrap: "nowrap",
        ...debug.name("contents"),
    });

    const close = style({
        color: "inherit",
        whiteSpace: "nowrap",
        fontWeight: globalVars.fonts.weights.semiBold,
        ...debug.name("close"),
    });

    const cancelContents = style({
        padding: px(4),
        ...debug.name("cancelContents"),
    });
    return { root, contents, close, cancelContents };
}
