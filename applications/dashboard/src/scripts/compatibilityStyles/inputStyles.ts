/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    absolutePosition,
    getHorizontalPaddingForTextInput,
    getVerticalPaddingForTextInput,
    importantUnit,
    negative,
    negativeUnit,
    textInputSizingFromFixedHeight,
} from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent, translateY } from "csx";
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
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
    const fg = ColorsUtils.colorOut(mainColors.fg);
    const bg = ColorsUtils.colorOut(mainColors.bg);
    const primary = ColorsUtils.colorOut(mainColors.primary);

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
        .file-upload-choose,
        .AdvancedSearch .InputBox,
        .AdvancedSearch select,
        select,
        div.token-input-dropdown.token-input-dropdown,
    `,
        {
            color: fg,
            backgroundColor: bg,
            ...Mixins.border(globalVars.borderType.formElements.default),
        },
    );

    cssOut(
        `
        #token-input-Form_tags,

        input.SmallInput:focus,
        input.InputBox:focus,
        .file-upload-choose:focus,
        textarea:focus
        `,
        {
            background: bg,
            color: fg,
        },
    );

    cssOut(`div.token-input-dropdown`, {
        ...Mixins.border(globalVars.borderType.dropDowns),
        transform: translateY(styleUnit(globalVars.border.width) as string),
    });

    cssOut(".token-input-input-token input", {
        ...textInputSizingFromFixedHeight(
            inputVars.sizing.height,
            inputVars.font.size as number,
            formVars.border.width * 2,
        ),
        border: important(0),
        paddingTop: important(0),
        paddingBottom: important(0),
    });

    mixinInputStyles(`input[type= "text"]`);
    mixinInputStyles("textarea");
    mixinInputStyles("input.InputBox");
    mixinInputStyles(".file-upload-choose");
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
        ...{
            "&:hover, &:focus, &.focus-visible, &:active": {
                borderColor: important(ColorsUtils.colorOut(globalVars.mainColors.primary) as string),
            },
        },
    });

    cssOut("form .SelectWrapper::after", {
        color: "inherit",
    });

    cssOut("form .SelectWrapper, .AdvancedSearch .Handle.Handle ", {
        color: ColorsUtils.colorOut(inputVars.colors.fg),
    });

    cssOut("form .SelectWrapper", {
        ...{
            "& select": {
                cursor: "pointer",
            },
            "&:hover, &:focus, &.focus-visible, &:active": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
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
        minHeight: styleUnit(formVars.sizing.height),
        paddingRight: important(0),
        paddingBottom: important(0),
    });

    // Real text input
    cssOut("ul.token-input-list li input", {
        boxSizing: "border-box",
        height: styleUnit(spaceWithoutPaddingInInput),
        paddingTop: important(0),
        paddingBottom: important(0),
        paddingLeft: important(0),
        minHeight: important("initial"),
        maxWidth: calc(`100% - ${styleUnit(horizontalPadding)}`),
        lineHeight: important(1),
        borderRadius: important(0),
        background: important("transparent"),
        border: important(0),
    });

    // Token
    cssOut("li.token-input-token.token-input-token", {
        margin: 0,
        padding: styleUnit(globalVars.meta.spacing.default),
        marginBottom: styleUnit(verticalPadding),
        lineHeight: styleUnit(globalVars.meta.text.lineHeight),
        minHeight: styleUnit(spaceWithoutPaddingInInput),
        ...Mixins.border({
            ...globalVars.borderType.formElements.default,
            color: globalVars.meta.colors.fg,
        }),
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "space-between",
        marginRight: important(styleUnit(horizontalPadding) as string),
    });

    // Text inside token
    cssOut("li.token-input-token.token-input-token p", {
        fontSize: styleUnit(globalVars.meta.text.size),
        lineHeight: styleUnit(globalVars.meta.text.lineHeight),
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    // "x" inside token
    cssOut("li.token-input-token span.token-input-delete-token", {
        ...{
            "&:hover, &:focus, &.focus-visible, &:active": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
    });

    // Inline Checkboxes:
    cssOut(".Checkboxes.Inline", {
        display: "flex",
        flexWrap: "wrap",
        width: calc(`100% + ${styleUnit(globalVars.meta.spacing.default * 2)}`),
        marginLeft: styleUnit(negative(globalVars.meta.spacing.default)),
        marginTop: styleUnit(globalVars.meta.spacing.default),
        ...{
            ".CheckBoxLabel": {
                cursor: "pointer",
                ...Mixins.margin({
                    all: 0,
                    right: styleUnit(globalVars.meta.spacing.default),
                    bottom: styleUnit(globalVars.meta.spacing.default),
                }),
            },
        },
    });

    cssOut("#Form_date", {
        marginRight: styleUnit(globalVars.gutter.half),
    });

    cssOut(`.FormWrapper label`, {
        fontSize: globalVars.fonts.size.medium,
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(`.js-datetime-picker`, {
        display: "flex",
        flexWrap: "wrap",
        width: calc(`100% + ${styleUnit(globalVars.meta.spacing.default * 2)}`),
        ...Mixins.margin({
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
        minWidth: styleUnit(200),
        maxWidth: percent(100),
        ...Mixins.margin({
            all: globalVars.meta.spacing.default,
        }),
    });

    const formSpacer = 8;

    cssOut(`.StructuredForm .P`, {
        ...Mixins.margin({
            vertical: globalVars.gutter.size,
            horizontal: 0,
        }),
    });

    cssOut(`.EventTime`, {
        ...Mixins.margin({
            left: negativeUnit(formSpacer),
        }),
        width: calc(`100% + ${styleUnit(formSpacer * 2)}`), // 2 inputs side by side
    });

    cssOut(`.EventTime .From, .EventTime .To`, {
        position: "relative",
        boxSizing: "border-box",
        width: calc(`50% - ${styleUnit(formSpacer * 2)}`),
        ...Mixins.margin({
            top: 0,
            horizontal: formSpacer,
        }),
    });

    cssOut(`.Event.add .DatePicker, .Event.edit .DatePicker`, {
        paddingRight: styleUnit(36),
        ...Mixins.margin({
            horizontal: 0,
            vertical: formSpacer,
        }),
    });

    cssOut(`.EventTime.Times .Timebased.EndTime`, {
        ...Mixins.margin({
            top: formSpacer,
            bottom: 0,
            horizontal: 0,
        }),
    });

    mixinTextLinkNoDefaultLinkAppearance(`.EventTime.Times .Timebased.NoEndTime a`);

    cssOut(`.EventTime.Times .Timebased.NoEndTime a`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        fontSize: styleUnit(20),
        cursor: "pointer",
    });

    cssOut(`.js-datetime-picker`, {
        margin: 0,
        width: percent(100),
    });

    cssOut(`.InputBox.InputBox.InputBox.TimePicker`, {
        flexGrow: 1,
        width: percent(100),
        ...Mixins.margin({
            all: 0,
        }),
    });

    cssOut(`.EventTime.Times.Both .Timebased.NoEndTime`, {
        ...absolutePosition.topRight(0, 6),
    });

    cssOut(`.StructuredForm input.hasDatepicker, .StructuredForm input.hasDatepicker:focus`, {
        backgroundPosition: "99% 50%", // Intentional, to have fallback in case `calc` is not supported
        backgroundPositionX: calc(`100% - ${styleUnit(5)}`),
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
    const primary = ColorsUtils.colorOut(globalVars.mainColors.primary);
    let extraFocus = {};
    if (focusSelector) {
        extraFocus = {
            [focusSelector]: {
                borderColor: isImportant ? important(primary as string) : primary,
            },
        };
    }

    cssOut(selector, {
        ...textInputSizingFromFixedHeight(vars.sizing.height, vars.font.size as number, formVars.border.width * 2),
        borderColor: ColorsUtils.colorOut(globalVars.border.color),
        borderStyle: isImportant ? important(globalVars.border.style) : globalVars.border.style,
        borderWidth: isImportant
            ? important(styleUnit(globalVars.border.width) as string)
            : styleUnit(globalVars.border.width),
        borderRadius: isImportant
            ? important(styleUnit(globalVars.border.radius) as string)
            : styleUnit(globalVars.border.radius),
        backgroundColor: isImportant
            ? important(ColorsUtils.colorOut(globalVars.mainColors.bg) as string)
            : ColorsUtils.colorOut(globalVars.mainColors.bg),
        color: isImportant
            ? important(ColorsUtils.colorOut(globalVars.mainColors.fg) as string)
            : ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(selector, {
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
        minHeight: styleUnit(formVars.sizing.height),
    });

    cssOut(`.TextBoxWrapper li.token-input-token.token-input-token`, {
        marginBottom: importantUnit(formVars.spacing.verticalPadding - formVars.border.width),
        marginRight: importantUnit(formVars.spacing.horizontalPadding - 2 * formVars.border.width),
    });

    cssOut(`li.token-input-token span`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    cssOut(`ul.token-input-list li input`, {
        marginBottom: importantUnit(4),
    });
};
