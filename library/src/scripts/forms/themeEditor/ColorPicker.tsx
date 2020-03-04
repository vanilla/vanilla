/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useRef, useState } from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import { colorPickerClasses } from "@library/forms/themeEditor/colorPickerStyles";
import { color, ColorHelper } from "csx";
import { ErrorMessage, useField, useFormikContext } from "formik";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n/src";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import { getDefaultOrCustomErrorMessage, isValidColor, stringIsValidColor } from "@library/styles/styleUtils";
type IErrorWithDefault = string | boolean; // Uses default message if true

export interface IColorPicker {
    inputProps?: Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex">;
    inputID: string;
    variableID: string;
    labelID: string;
    defaultValue?: ColorHelper;
    inputClass?: string;
    errors?: IErrorWithDefault[]; // Uses default message if true
    errorMessage?: string;
}

export default function ColorPicker(props: IColorPicker) {
    const classes = colorPickerClasses();
    const colorInput = useRef<HTMLInputElement>(null);
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();
    const errorMessage = getDefaultOrCustomErrorMessage(props.errorMessage, t("Invalid Color"));

    const errorID = useMemo(() => {
        return uniqueIDFromPrefix("colorPickerError");
    }, []);

    // String
    const initialValidColor =
        props.defaultValue && isValidColor(props.defaultValue.toString()) ? props.defaultValue.toString() : undefined;

    const [selectedColor, selectedColorMeta, helpers] = useField(props.variableID);
    const { setFieldError } = useFormikContext();

    const [validColor, setValidColor] = useState(initialValidColor);
    const [errorField, errorMeta, errorHelpers] = useField("errors." + props.variableID);

    const clickReadInput = () => {
        if (colorInput && colorInput.current) {
            colorInput.current.click();
        }
    };

    const onTextChange = e => {
        const colorString = e.target.value;
        helpers.setValue(colorString); //Text is unchanged
        helpers.setTouched(true);
        if (stringIsValidColor(colorString)) {
            setValidColor(colorString); // Only set valid color if passes validation
            errorHelpers.setValue(undefined);
        } else {
            errorHelpers.setValue(true);
        }
    };

    const onPickerChange = e => {
        // Will always be valid color, since it's a real picker
        const newColor: string = e.target.value;
        if (newColor) {
            helpers.setTouched(true);
            helpers.setValue(newColor);
            setValidColor(
                color(newColor)
                    .toRGB()
                    .toString(),
            );
            errorHelpers.setValue(undefined);
        }
    };

    const textValue =
        selectedColor.value !== undefined
            ? selectedColor.value
            : props.defaultValue && isValidColor(props.defaultValue)
            ? props.defaultValue.toHexString()
            : "";

    const hasError = !isValidColor(textValue);

    // Check initial value for errors
    useEffect(() => {
        if (hasError) {
            helpers.setError(true);
        } else {
            helpers.setError(false);
        }
    }, []);

    return (
        <>
            <span className={classes.root}>
                <input className={visibility().displayNone} value={errorField.value} />

                {/*"Real" color input*/}
                <input
                    {...props.inputProps}
                    ref={colorInput}
                    type="color"
                    id={props.inputID}
                    aria-describedby={props.labelID}
                    className={classNames(classes.realInput, visibility().visuallyHidden)}
                    onChange={onPickerChange}
                    onBlur={onPickerChange}
                    aria-errormessage={hasError ? errorID : undefined}
                    defaultValue={initialValidColor}
                />
                {/*Text Input*/}
                <input
                    ref={textInput}
                    type="text"
                    aria-describedby={props.labelID}
                    aria-hidden={true}
                    className={classNames(classes.textInput, {
                        [builderClasses.invalidField]: hasError,
                    })}
                    placeholder={"#0291DB"}
                    value={textValue}
                    // onKeyDown={onTextChange}
                    onChange={onTextChange}
                    auto-correct="false"
                />
                {/*Swatch*/}
                <Button
                    onClick={clickReadInput}
                    style={{ backgroundColor: color(validColor ?? "#000").toString() }}
                    title={validColor}
                    aria-hidden={true}
                    className={classes.swatch}
                    tabIndex={-1}
                    baseClass={ButtonTypes.CUSTOM}
                >
                    <span className={visibility().visuallyHidden}>{color(validColor ?? "#000").toHexString()}</span>
                </Button>
            </span>
            {hasError && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    <li className={builderClasses.error}>{errorMessage}</li>
                </ul>
            )}
        </>
    );
}
