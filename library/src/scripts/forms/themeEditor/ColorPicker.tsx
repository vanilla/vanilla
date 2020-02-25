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
import { isValidColor } from "@library/styles/styleUtils";

type IErrorWithDefault = string | boolean; // Uses default message if true

export interface IColorPicker {
    inputProps?: Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex">;
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

export const ensureColorHelper = (colorValue: string | ColorHelper) => {
    return typeof colorValue === "string" ? color(colorValue) : colorValue;
};

export default function ColorPicker(props: IColorPicker) {
    const classes = colorPickerClasses();
    const colorInput = useRef<HTMLInputElement>(null);
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();

    const errorID = useMemo(() => {
        return uniqueIDFromPrefix("colorPickerError");
    }, []);

    const initialColor = props.defaultValue;
    const initialValidColor = isValidColor(initialColor) ? ensureColorHelper(initialColor as any) : color("#000");

    const [selectedColor, selectedColorMeta, helpers] = useField({
        name: props.variableID,
        onBlur: props.inputProps ? props.inputProps.onBlur : undefined,
        onChange: props.inputProps ? props.inputProps.onChange : undefined,
        value: colorOut(initialColor),
    });

    const [validColor, setValidColor] = useState(initialValidColor);

    const clickReadInput = () => {
        if (colorInput && colorInput.current) {
            colorInput.current.click();
        }
    };

    const onTextInputChange = e => {
        const colorString = e.target.value;
        if (isValidColor(colorString)) {
            setValidColor(color(colorString)); // Only set valid color if passes validation
        }
        helpers.setValue(e.target.value); // Text is unchanged
    };

    const onColorInputChange = e => {
        // Will always be valid color, since it's a real picker
        const newColor = color(e.target.value);
        setValidColor(newColor);
        helpers.setValue(newColor);
    };

    const hasError = !isValidColor(selectedColor as any);
    const validColorString = colorOut(validColor);

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
                    value={validColorString}
                />
                {/*Text Input*/}
                <input
                    ref={textInput}
                    type="text"
                    aria-describedby={props.labelID}
                    aria-hidden={true}
                    className={classNames(classes.textInput, {
                        [classes.invalidColor]: hasError,
                    })}
                    placeholder={"#0291DB"}
                    value={selectedColor.toString()}
                    onChange={onTextInputChange}
                />
                {/*Swatch*/}
                <Button
                    onClick={clickReadInput}
                    style={{ backgroundColor: validColorString }}
                    title={validColorString}
                    aria-hidden={true}
                    className={classes.swatch}
                    tabIndex={-1}
                    baseClass={ButtonTypes.CUSTOM}
                >
                    <span className={visibility().visuallyHidden}>{validColorString}</span>
                </Button>
            </span>
            {selectedColorMeta.error && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    <li className={builderClasses.error}>
                        {getDefaultOrCustomErrorMessage(builderClasses.error, t("Invalid color"))}
                    </li>
                </ul>
            )}
        </>
    );
}
