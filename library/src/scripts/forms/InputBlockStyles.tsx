/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent } from "csx";
import { Property } from "csstype";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { tokensClasses } from "@library/forms/select/tokensStyles";
import { css } from "@emotion/css";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import { Mixins } from "@library/styles/Mixins";

export const inputBlockClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();

    const inputText = css({
        display: "block",
    });

    const inputWrap = css({
        display: "block",
    });

    const labelAndDescription = css({
        display: "block",
        width: percent(100),
        color: ColorsUtils.colorOut(formElementVars.colors.fg),
        marginBottom: 4,
    });

    const root = css({
        display: "block",
        width: percent(100),
        margin: 0,
        ...{
            [`& + &`]: {
                marginTop: styleUnit(formElementVars.spacing.margin),
            },
            [`&.isLast`]: {
                marginBottom: styleUnit(formElementVars.spacing.margin),
            },
            [`&.hasError .${inputText}`]: {
                borderColor: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
                backgroundColor: ColorsUtils.colorOut(globalVars.messageColors.error.bg),
                color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
            },
            "&.isHorizontal": {
                display: "flex",
                width: percent(100),
                alignItems: "center",
                flexWrap: "wrap",
            },
            [`&.isHorizontal .${labelAndDescription}`]: {
                display: "inline-flex",
                width: "auto",
            },
            [`&.isHorizontal .${inputWrap}`]: {
                display: "inline-flex",
                flexGrow: 1,
            },
            [`&.${tokensClasses().withIndicator} .tokens__value-container`]: {
                paddingRight: styleUnit(inputVariables().sizing.height),
            },
            [`&.${tokensClasses().withIndicator} .tokens__indicators`]: {
                position: "absolute",
                top: 0,
                right: 6,
                bottom: 0,
            },
        },
    });

    const errors = css({
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
        }),
    });

    const errorsPadding = css(inputClasses().inputPaddingMixin);

    const extendErrorPadding = css({
        ...Mixins.padding({
            horizontal: 0,
        }),
    });

    const error = css({
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "normal"),
        }),
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        ...{
            "& + &": {
                marginTop: styleUnit(6),
            },
        },
    });
    const labelNote = css({
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "normal"),
        }),
        opacity: 0.6,
    });

    const noteAfterInput = css({
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "normal"),
        }),
        marginTop: 4, // This magic number to match the labels margin bottom
        opacity: 0.6,
    });

    const labelText = css({
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
        }),
    });

    const sectionTitle = css({
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.base,
    });

    const fieldsetGroup = css({
        marginTop: 4,
        ...{
            "&.noMargin": {
                marginTop: styleUnit(0),
            },
        },
    });

    const multiLine = (resize?: Property.Resize, overflow?: Property.Overflow) => {
        return css({
            ...Mixins.padding({ vertical: 9 }),
            resize: (resize ? resize : "vertical") as Property.Resize,
            overflow: (overflow ? overflow : "auto") as Property.Overflow,
        });
    };

    const related = css({
        marginTop: styleUnit(globalVars.gutter.size),
    });

    const grid = css({
        display: "flex",
        flexWrap: "wrap",
        ...{
            [`.${checkRadioClasses().root}`]: {
                width: percent(50),
                alignItems: "flex-start",
            },
            [`&.${fieldsetGroup}`]: {
                marginTop: styleUnit(9),
            },
        },
    });

    const tight = css({
        ...{
            [`&&&`]: {
                marginTop: negativeUnit(9),
            },
        },
    });

    return {
        root,
        inputText,
        errors,
        errorsPadding,
        error,
        labelNote,
        labelText,
        inputWrap,
        labelAndDescription,
        sectionTitle,
        fieldsetGroup,
        multiLine,
        related,
        grid,
        tight,
        noteAfterInput,
        extendErrorPadding,
    };
});
