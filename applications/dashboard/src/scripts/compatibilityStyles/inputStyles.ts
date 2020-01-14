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
    cssOut(".token-input-list", {
        paddingLeft: important(0),
        paddingRight: important(0),
    });

    cssOut(".token-input-input-token input", {
        ...textInputSizingFromFixedHeight(inputVars.sizing.height, inputVars.font.size, formVars.border.fullWidth),
    });

    mixinInputStyles(`input[type= "text"]`);
    mixinInputStyles("textarea");
    mixinInputStyles("ul.token-input-list");
    mixinInputStyles("input.InputBox");
    mixinInputStyles(".InputBox");
    mixinInputStyles(".AdvancedSearch select");
    mixinInputStyles("select");
    mixinInputStyles(".InputBox.BigInput");
    mixinInputStyles("ul.token-input-list", "& .token-list-focused");
    mixinInputStyles(`
        .Container input[type= "text"],
        .Container textarea, ul.token-input-list,
        .Container input.InputBox,
        .Container .AdvancedSearch .InputBox,
        .Container .AdvancedSearch select,
        .Container select
        `);
    mixinInputStyles(".Container ul.token-input-list", ".Container ul.token-input-list.token-input-focused");
    mixinInputStyles(".token-input-list, .token-input-focused");
    mixinInputStyles(".input:-internal-autofill-selected", false, true);
    cssOut(".InputBox.InputBox.InputBox", inputClasses().inputMixin);
    cssOut(".token-input-list", inputClasses().inputMixin);

    cssOut(".token-input-input-token input", {
        border: important(0),
    });
};

export const mixinInputStyles = (selector: string, focusSelector?: string | false, isImportant = false) => {
    const vars = globalVariables();
    selector = trimTrailingCommas(selector);
    const primary = colorOut(vars.mainColors.primary);
    let extraFocus = {};
    if (focusSelector) {
        extraFocus = {
            [focusSelector]: {
                borderColor: isImportant ? important(primary as string) : primary,
            },
        };
    }

    cssOut(selector, {
        borderColor: colorOut(vars.border.color),
        borderStyle: isImportant ? important(vars.border.style) : vars.border.style,
        borderWidth: isImportant ? important(unit(vars.border.width) as string) : unit(vars.border.width),
        backgroundColor: isImportant ? important(colorOut(vars.mainColors.bg) as string) : colorOut(vars.mainColors.bg),
        color: isImportant ? important(colorOut(vars.mainColors.fg) as string) : colorOut(vars.mainColors.fg),
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
