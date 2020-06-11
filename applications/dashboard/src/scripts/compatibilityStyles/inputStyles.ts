/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    absolutePosition,
    borders,
    colorOut,
    getHorizontalPaddingForTextInput,
    getVerticalPaddingForTextInput,
    importantUnit,
    margins,
    negative,
    negativeUnit,
    textInputSizingFromFixedHeight,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent, translateY } from "csx";
import { cssOut, nestedWorkaround, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { inputVariables, inputMixin } from "@library/forms/inputStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { mixinTextLinkNoDefaultLinkAppearance } from "@dashboard/compatibilityStyles/textLinkStyles";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";

export const inputCSS = () => {
    wrapSelects();

    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);

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
            ...borders(globalVars.borderType.formElements.default),
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
        ...borders(globalVars.borderType.dropDowns),
        transform: translateY(unit(globalVars.border.width) as string),
    });

    cssOut(".token-input-input-token input", {
        ...textInputSizingFromFixedHeight(inputVars.sizing.height, inputVars.font.size, formVars.border.width * 2),
        border: important(0),
        paddingTop: important(0),
        paddingBottom: important(0),
    });

    mixinInputStyles(`input[type= "text"]`);
    mixinInputStyles("textarea");
    mixinInputStyles("input.InputBox");
    mixinInputStyles(".InputBox");
    mixinInputStyles(".InputBox.BigInput");
    mixinInputStyles("ul.token-input-list, div.Popup .Body ul.token-input-list");
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
    cssOut(".InputBox.InputBox.InputBox", inputMixin());
    cssOut(`.richEditor-frame.InputBox.InputBox.InputBox `, { padding: 0 });
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
        color: colorOut(inputVars.colors.fg),
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

    cssOut(".AdvancedSearch .Handle.Handle .Arrow::after", {
        color: "inherit",
    });

    // Token inputs
    const verticalPadding = getVerticalPaddingForTextInput(
        formVars.sizing.height,
        globalVars.fonts.size.small,
        formVars.border.width * 2,
    );
    const horizontalPadding = getHorizontalPaddingForTextInput(
        formVars.sizing.height,
        globalVars.fonts.size.small,
        formVars.border.width * 2,
    );

    const spaceWithoutPaddingInInput = formVars.sizing.height - verticalPadding * 2 - formVars.border.width * 2;

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
            ...globalVars.borderType.formElements.default,
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

    cssOut("#Form_date", {
        marginRight: unit(globalVars.gutter.half),
    });

    cssOut(`.FormWrapper label`, {
        fontSize: globalVars.fonts.size.medium,
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`.js-datetime-picker`, {
        display: "flex",
        flexWrap: "wrap",
        width: calc(`100% + ${unit(globalVars.meta.spacing.default * 2)}`),
        ...margins({
            left: -globalVars.meta.spacing.default,
            right: globalVars.meta.spacing.default,
        }),
    });

    cssOut(`.EventTime`, {
        display: "flex",
        flexWrap: "nowrap",
    });

    cssOut(`.InputBox.DatePicker`, {
        flexGrow: 1,
        minWidth: unit(200),
        maxWidth: percent(100),
        ...margins({
            all: globalVars.meta.spacing.default,
        }),
    });

    const formSpacer = 8;

    cssOut(`.StructuredForm .P`, {
        ...margins({
            vertical: globalVars.gutter.size,
            horizontal: 0,
        }),
    });

    cssOut(`.EventTime`, {
        ...margins({
            left: negativeUnit(formSpacer),
        }),
        width: calc(`100% + ${unit(formSpacer * 2)}`), // 2 inputs side by side
    });

    cssOut(`.EventTime .From, .EventTime .To`, {
        position: "relative",
        boxSizing: "border-box",
        width: calc(`50% - ${unit(formSpacer * 2)}`),
        ...margins({
            top: 0,
            horizontal: formSpacer,
        }),
    });

    cssOut(`.Event.add .DatePicker, .Event.edit .DatePicker`, {
        paddingRight: unit(36),
        ...margins({
            horizontal: 0,
            vertical: formSpacer,
        }),
    });

    cssOut(`.EventTime.Times .Timebased.EndTime`, {
        ...margins({
            top: formSpacer,
            bottom: 0,
            horizontal: 0,
        }),
    });

    mixinTextLinkNoDefaultLinkAppearance(`.EventTime.Times .Timebased.NoEndTime a`);

    cssOut(`.EventTime.Times .Timebased.NoEndTime a`, {
        color: colorOut(globalVars.mainColors.fg),
        fontSize: unit(20),
        cursor: "pointer",
    });

    cssOut(`.js-datetime-picker`, {
        margin: 0,
        width: percent(100),
    });

    cssOut(`.InputBox.InputBox.InputBox.TimePicker`, {
        flexGrow: 1,
        width: percent(100),
        ...margins({
            all: 0,
        }),
    });

    cssOut(`.EventTime.Times.Both .Timebased.NoEndTime`, {
        ...absolutePosition.topRight(0, 6),
    });

    cssOut(`.StructuredForm input.hasDatepicker, .StructuredForm input.hasDatepicker:focus`, {
        backgroundPosition: "99% 50%", // Intentional, to have fallback in case `calc` is not supported
        backgroundPositionX: calc(`100% - ${unit(5)}`),
    });
};

function wrapSelects() {
    const selects = document.querySelectorAll("select");
    selects.forEach((selectElement: HTMLElement) => {
        const wrapper = document.createElement("div");
        wrapper.classList.add("SelectWrapper");
        selectElement.parentElement?.insertBefore(wrapper, selectElement);
        wrapper.appendChild(selectElement);
    });
}

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
        ...textInputSizingFromFixedHeight(vars.sizing.height, vars.font.size, formVars.border.width * 2),
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

    cssOut(`ul.token-input-list, div.Popup .Body ul.token-input-list`, {
        paddingBottom: importantUnit(0),
        paddingRight: importantUnit(0),
        minHeight: unit(formVars.sizing.height),
    });

    cssOut(`.TextBoxWrapper li.token-input-token.token-input-token`, {
        marginBottom: importantUnit(formVars.spacing.verticalPadding - formVars.border.width),
        marginRight: importantUnit(formVars.spacing.horizontalPadding - 2 * formVars.border.width),
    });

    cssOut(`li.token-input-token span`, {
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`ul.token-input-list li input`, {
        marginBottom: importantUnit(4),
    });
};
