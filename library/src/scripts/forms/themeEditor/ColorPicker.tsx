/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useRef, useState } from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import { colorPickerClasses } from "@library/forms/themeEditor/colorPickerStyles";
import { color, ColorHelper } from "csx";
import { colorOut } from "@library/styles/styleHelpersColors";
import { useField } from "formik";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n/src";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";

const Collit = require("collit");

type IErrorWithDefault = string | boolean; // Uses default message if true

export interface IColorPicker {
    inputProps?: React.HTMLAttributes<Omit<Omit<Omit<HTMLInputElement, "type">, "id">, "tabIndex">>;
    inputID: string;
    variableID: string;
    labelID: string;
    defaultValue?: ColorHelper;
    inputClass?: string;
    errors?: IErrorWithDefault[]; // Uses default message if true
}

export const getDefaultOrCustomErrorMessage = (error: string | true, defaultMessage: string) => {
    return typeof error === "string" ? error : defaultMessage;
};

export default function ColorPicker(props: IColorPicker) {
    const classes = colorPickerClasses();
    const colorInput = useRef<HTMLInputElement>(null);
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();

    const errorID = useMemo(() => {
        return uniqueIDFromPrefix("colorPickerError");
    }, []);

    const initialColor = props.defaultValue ? props.defaultValue : color("#fff");

    const [lastValidColor, setLastValidColor] = useState(initialColor); // Always "Color" object
    const [inputTextValue, setInputTextValue] = useState(initialColor.toString()); // Always string

    const [selectedColor, selectedColorMeta, selectedColorHelpers] = useField({
        name: props.variableID,
        onBlur: props.inputProps ? props.inputProps.onBlur : undefined,
        onChange: props.inputProps ? props.inputProps.onChange : undefined,
        value: colorOut(initialColor),
    });

    const isValidColorString = (color: string | undefined) => {
        if (color) {
            const Validator = Collit.Validator;
            return (
                typeof color === "string" &&
                (Validator.isHex(color) ||
                    Validator.isRgb(color) ||
                    Validator.isRgba(color) ||
                    Validator.isHsl(color) ||
                    Validator.isHsla(color))
            );
        }
    };

    const clickReadInput = () => {
        if (colorInput && colorInput.current) {
            colorInput.current.click();
        }
    };

    const onTextInputChange = e => {
        const colorString = e.target.value;
        if (isValidColorString(colorString)) {
            const newColor = color(colorString);
            selectedColorHelpers.setValue(newColor);
            setLastValidColor(newColor);
        }
        setInputTextValue(e.target.value);
    };

    const onColorInputChange = e => {
        const newColor = color(e.target.value);
        selectedColorHelpers.setValue(newColor);
        setLastValidColor(newColor);
        setInputTextValue(newColor.toString());
    };

    const formattedColor = colorOut(lastValidColor);
    const hasError = props.errors && props.errors.length > 0;

    return (
        <>
            <span className={classes.root}>
                {/*"Real" color input*/}
                <input
                    {...props.inputProps}
                    ref={colorInput}
                    type="color"
                    id={props.inputID}
                    aria-describedby={props.labelID}
                    className={classNames(classes.realInput, visibility().visuallyHidden)}
                    onChange={onColorInputChange}
                    aria-errormessage={hasError ? errorID : undefined}
                    defaultValue={initialColor.toString()}
                />
                {/*Text Input*/}
                <input
                    ref={textInput}
                    type="text"
                    aria-describedby={props.labelID}
                    aria-hidden={true}
                    className={classNames(classes.textInput, {
                        [classes.invalidColor]: !isValidColorString(inputTextValue),
                    })}
                    placeholder={"#0291DB"}
                    value={inputTextValue}
                    onChange={onTextInputChange}
                />
                {/*Swatch*/}
                <Button
                    onClick={clickReadInput}
                    style={{ backgroundColor: isValidColorString(formattedColor) ? formattedColor : "#000" }}
                    title={formattedColor}
                    aria-hidden={true}
                    className={classes.swatch}
                    tabIndex={-1}
                    baseClass={ButtonTypes.CUSTOM}
                >
                    <span className={visibility().visuallyHidden}>{formattedColor}</span>
                </Button>
            </span>
            {props.errors && props.errors.length > 0 && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    {props.errors.map((error, i) => {
                        return (
                            <li className={builderClasses.error} key={i}>
                                {error && getDefaultOrCustomErrorMessage(error, t("Invalid color"))}
                            </li>
                        );
                    })}
                </ul>
            )}
        </>
    );
}
