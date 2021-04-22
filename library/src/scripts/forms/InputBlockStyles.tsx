/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent } from "csx";
import { Property } from "csstype";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import { tokensClasses } from "@library/forms/select/tokensStyles";
import { CSSObject } from "@emotion/css";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import { Mixins } from "@library/styles/Mixins";

export const inputBlockClasses = useThemeCache(() => {
    const style = styleFactory("inputBlock");
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();

    const inputText = style("inputText", {
        display: "block",
    });

    const inputWrap = style("inputWrap", {
        display: "block",
    });

    const labelAndDescription = style("labelAndDescription", {
        display: "block",
        width: percent(100),
        color: ColorsUtils.colorOut(formElementVars.colors.fg),
    });

    const root = style({
        display: "block",
        width: percent(100),
        ...{
            [`& + &`]: {
                marginTop: styleUnit(formElementVars.spacing.margin),
            },
            [`&.isLast`]: {
                marginBottom: styleUnit(formElementVars.spacing.margin),
            },
            [`&.hasError .${inputText}`]: {
                borderColor: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
                backgroundColor: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
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

    const errors = style("errors", {
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
        }),
    });

    const errorsPadding = style("errorsPadding", inputClasses().inputPaddingMixin);

    const error = style("error", {
        display: "block",
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        ...{
            "& + &": {
                marginTop: styleUnit(6),
            },
        },
    });
    const labelNote = style("labelNote", {
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "normal"),
        }),
        opacity: 0.6,
    });

    const labelText = style("labelText", {
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "semiBold"),
        }),
        marginBottom: styleUnit(formElementVars.spacing.margin),
    });

    const sectionTitle = style("sectionTitle", {
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: globalVars.lineHeights.base,
    });

    const fieldsetGroup = style("fieldsetGroup", {
        marginTop: styleUnit(formElementVars.spacing.margin),
        ...{
            "&.noMargin": {
                marginTop: styleUnit(0),
            },
        },
    });

    const multiLine = (resize?: Property.Resize, overflow?: Property.Overflow) => {
        return style("multiLine", {
            ...Mixins.padding({ vertical: 9 }),
            resize: (resize ? resize : "vertical") as Property.Resize,
            overflow: (overflow ? overflow : "auto") as Property.Overflow,
        });
    };

    const related = style("related", {
        marginTop: styleUnit(globalVars.gutter.size),
    });

    const grid = style("grid", {
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

    const tight = style("tight", {
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
    };
});
