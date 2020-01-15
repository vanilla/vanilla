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
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const inputCSS = () => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(globalVars.meta.colors.fg);

    cssOut("ul.token-input-list.token-input-focused, .AdvancedSearch .InputBox:focus", {
        borderColor: primary,
    });

    cssOut(
        `
        input[type= "text"],
        textarea,
        input.InputBox,
        .AdvancedSearch .InputBox,
        .AdvancedSearch select,
        select,
        div.token-input-dropdown.token-input-dropdown,
    `,
        {
            borderRadius: unit(formVars.border.radius),
            color: fg,
            backgroundColor: bg,
            borderColor: fg,
        },
    );

    cssOut(
        `
        #token-input-Form_tags,
        input[type= "text"],
        textarea,
        input.InputBox,
        .InputBox,
        .AdvancedSearch select,
        select,
        .InputBox.BigInput,
        input.SmallInput:focus,
        input.InputBox:focus,
        textarea:focus
        `,
        {
            background: bg,
            color: fg,
        },
    );

    cssOut(`div.token-input-dropdown`, borders());

    // The padding here needs to be removed so the autocomplete calculates the width properly.
    // cssOut(".token-input-list", {
    //     padding: important(0),
    // });

    cssOut(".token-input-input-token input", {
        ...textInputSizingFromFixedHeight(inputVars.sizing.height, inputVars.font.size, formVars.border.fullWidth),
        border: important(0),
        paddingTop: important(0),
        paddingBottom: important(0),
    });

    mixinInputStyles(`input[type= "text"]`);
    mixinInputStyles("textarea");
    mixinInputStyles("input.InputBox");
    mixinInputStyles(".InputBox");
    mixinInputStyles(".AdvancedSearch select");
    mixinInputStyles("select");
    mixinInputStyles(".InputBox.BigInput");
    mixinInputStyles(`
        .Container input[type= "text"],
        .Container textarea,
        .Container input.InputBox,
        .Container .AdvancedSearch .InputBox,
        .Container .AdvancedSearch select,
        .Container select
        `);
    mixinInputStyles(".Container ul.token-input-list", ".Container ul.token-input-list.token-input-focused");
    mixinInputStyles(".token-input-list, .token-input-focused");
    mixinInputStyles(".input:-internal-autofill-selected", false, true);
    mixinInputStyles(".AdvancedSearch .InputBox", false, true);
    cssOut(".InputBox.InputBox.InputBox", inputClasses().inputMixin);
    cssOut(".token-input-list", inputClasses().inputMixin);

    cssOut("ul.token-input-list li input", {
        // paddingLeft: unit(inputVars.sizing.height * 2),
        // paddingRight: unit(inputVars.sizing.height * 2),
        // paddingLeft: important(0),
        // paddingRight: important(0),
        padding: important(0),
        minHeight: important("initial"),
        lineHeight: important(1),
    });
};

export const mixinInputStyles = (selector: string, focusSelector?: string | false, isImportant = false) => {
    const globalVars = globalVariables();
    const vars = inputVariables();
    selector = trimTrailingCommas(selector);
    const formElementVars = formElementsVariables();
    const primary = colorOut(globalVars.mainColors.primary);
    let extraFocus = {};
    if (focusSelector) {
        extraFocus = {
            [focusSelector]: {
                borderColor: isImportant ? important(primary as string) : primary,
            },
        };
    }

    cssOut(selector, {
        ...textInputSizingFromFixedHeight(vars.sizing.height, vars.font.size, formElementVars.border.fullWidth),
        borderColor: colorOut(globalVars.border.color),
        borderStyle: isImportant ? important(globalVars.border.style) : globalVars.border.style,
        borderWidth: isImportant ? important(unit(globalVars.border.width) as string) : unit(globalVars.border.width),
        backgroundColor: isImportant
            ? important(colorOut(globalVars.mainColors.bg) as string)
            : colorOut(globalVars.mainColors.bg),
        color: isImportant
            ? important(colorOut(globalVars.mainColors.fg) as string)
            : colorOut(globalVars.mainColors.fg),
    });

    nestedWorkaround(selector, {
        "&:focus": {
            borderColor: isImportant ? important(primary as string) : primary,
        },
        "&.focus-visible": {
            borderColor: isImportant ? important(primary as string) : primary,
        },
        "&.token-input-highlighted-token": {
            borderColor: isImportant ? important(primary as string) : primary,
        },
        ...extraFocus,
    });
};
