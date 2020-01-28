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
    getHorizontalPaddingForTextInput,
    getVerticalPaddingForTextInput,
    margins,
    negative,
    pointerEvents,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent, translateY } from "csx";
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

    cssOut(
        `
        .Container ul.token-input-list.token-input-focused,
        .AdvancedSearch .InputBox:focus`,
        {
            borderColor: primary,
        },
    );

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
            color: fg,
            backgroundColor: bg,
            ...borders(),
        },
    );

    cssOut(
        `
        #token-input-Form_tags,

        input.SmallInput:focus,
        input.InputBox:focus,
        textarea:focus
        `,
        {
            background: bg,
            color: fg,
        },
    );

    cssOut(`div.token-input-dropdown`, {
        // outline: `solid ${unit(globalVars.border.width * 2)} ${colorOut(globalVars.mainColors.primary)}`,
        ...borders(),
        transform: translateY(unit(globalVars.border.width) as string),
    });

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
    // mixinInputStyles(".AdvancedSearch select");
    // mixinInputStyles("select");
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
    mixinInputStyles(".input:-internal-autofill-selected", false, true);
    mixinInputStyles(".AdvancedSearch .InputBox", false, false);
    cssOut(".InputBox.InputBox.InputBox", inputClasses().inputMixin);
    // cssOut(".token-input-list", inputClasses().inputMixin);
    cssOut("select", {
        $nest: {
            "&:hover, &:focus, &.focus-visible, &:active": {
                borderColor: important(colorOut(globalVars.mainColors.primary) as string),
            },
        },
    });

    cssOut("form .SelectWrapper::after", {
        color: "inherit",
    });

    cssOut("form .SelectWrapper, .AdvancedSearch .Handle.Handle ", {
        color: colorOut(globalVars.border.color),
    });

    cssOut("form .SelectWrapper", {
        $nest: {
            "& select": {
                cursor: "pointer",
            },
            "&:hover, &:focus, &.focus-visible, &:active": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    cssOut(".Handle.Handle", {
        $nest: {
            "&:hover, &:focus, &.focus-visible, &:active": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    cssOut(".AdvancedSearch .Handle.Handle .Arrow::after", {
        color: "inherit",
    });

    // Token inputs
    const verticalPadding = getVerticalPaddingForTextInput(
        formVars.sizing.height,
        globalVars.fonts.size.small,
        formVars.border.fullWidth,
    );
    const horizontalPadding = getHorizontalPaddingForTextInput(
        formVars.sizing.height,
        globalVars.fonts.size.small,
        formVars.border.fullWidth,
    );

    const spaceWithoutPaddingInInput = formVars.sizing.height - verticalPadding * 2 - formVars.border.fullWidth;

    // Container of tokens
    cssOut(".Container ul.token-input-list", {
        minHeight: unit(formVars.sizing.height),
        paddingRight: important(0),
        paddingBottom: important(0),
    });

    // Real text input
    cssOut("ul.token-input-list li input", {
        boxSizing: "border-box",
        height: unit(spaceWithoutPaddingInInput),
        paddingTop: important(0),
        paddingBottom: important(0),
        paddingLeft: important(0),
        minHeight: important("initial"),
        maxWidth: calc(`100% - ${unit(horizontalPadding)}`),
        lineHeight: important(1),
        borderRadius: important(0),
        background: important("transparent"),
        border: important(0),
    });

    // Token
    cssOut("li.token-input-token.token-input-token", {
        margin: 0,
        padding: unit(globalVars.meta.spacing.default),
        marginBottom: unit(verticalPadding),
        lineHeight: unit(globalVars.meta.lineHeights.default),
        minHeight: unit(spaceWithoutPaddingInInput),
        ...borders({
            color: globalVars.meta.colors.fg,
        }),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "space-between",
        marginRight: important(unit(horizontalPadding) as string),
    });

    // Text inside token
    cssOut("li.token-input-token.token-input-token p", {
        fontSize: unit(globalVars.meta.text.fontSize),
        lineHeight: unit(globalVars.meta.lineHeights.default),
        color: colorOut(globalVars.mainColors.fg),
    });

    // "x" inside token
    cssOut("li.token-input-token span.token-input-delete-token", {
        $nest: {
            "&:hover, &:focus, &.focus-visible, &:active": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    // Inline Checkboxes:
    cssOut(".Checkboxes.Inline", {
        display: "flex",
        flexWrap: "wrap",
        width: calc(`100% + ${unit(globalVars.meta.spacing.default * 2)}`),
        marginLeft: unit(negative(globalVars.meta.spacing.default)),
        marginTop: unit(globalVars.meta.spacing.default),
        $nest: {
            "& .CheckBoxLabel": {
                cursor: "pointer",
                ...margins({
                    all: 0,
                    right: unit(globalVars.meta.spacing.default),
                    bottom: unit(globalVars.meta.spacing.default),
                }),
            },
        },
    });

    cssOut("input[type='checkbox']", {
        cursor: "pointer",
        $nest: {
            "&:hover, &:focus, &.focus-visible, &:active": {
                outline: `solid ${unit(globalVars.border.width * 2)} ${colorOut(globalVars.mainColors.primary)}`,
            },
        },
    });

    cssOut("#Form_date", {
        marginRight: unit(globalVars.gutter.half),
    });
};

export const mixinInputStyles = (selector: string, focusSelector?: string | false, isImportant = false) => {
    const globalVars = globalVariables();
    const vars = inputVariables();
    selector = trimTrailingCommas(selector);
    const formVars = formElementsVariables();
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
        ...textInputSizingFromFixedHeight(vars.sizing.height, vars.font.size, formVars.border.fullWidth),
        borderColor: colorOut(globalVars.border.color),
        borderStyle: isImportant ? important(globalVars.border.style) : globalVars.border.style,
        borderWidth: isImportant ? important(unit(globalVars.border.width) as string) : unit(globalVars.border.width),
        borderRadius: isImportant
            ? important(unit(globalVars.border.radius) as string)
            : unit(globalVars.border.radius),
        backgroundColor: isImportant
            ? important(colorOut(globalVars.mainColors.bg) as string)
            : colorOut(globalVars.mainColors.bg),
        color: isImportant
            ? important(colorOut(globalVars.mainColors.fg) as string)
            : colorOut(globalVars.mainColors.fg),
    });

    nestedWorkaround(selector, {
        "&:active": {
            borderColor: isImportant ? important(primary as string) : primary,
        },
        "&:hover": {
            borderColor: isImportant ? important(primary as string) : primary,
        },
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
