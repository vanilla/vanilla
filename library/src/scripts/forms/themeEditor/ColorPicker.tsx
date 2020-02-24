/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import { colorPickerClasses } from "@library/forms/themeEditor/colorPickerStyles";
import { color, ColorHelper } from "csx";
import { colorOut } from "@library/styles/styleHelpersColors";
import { useField } from "formik";
const Collit = require("collit");

export interface IColorPicker {
    inputProps?: React.HTMLAttributes<Omit<Omit<Omit<HTMLInputElement, "type">, "id">, "tabIndex">>;
    inputID: string;
    variableID: string;
    labelID: string;
    initialColor?: ColorHelper;
    inputClass?: string;
}

export default function ColorPicker(props: IColorPicker) {
    const classes = colorPickerClasses();
    const colorInput = useRef<HTMLInputElement>(null);
    const textInput = useRef<HTMLInputElement>(null);

    const initialColor = props.initialColor ? props.initialColor : color("#000");

    const [lastValidColor, setLastValidColor] = useState(initialColor);
    const [inputTextValue, setInputTextValue] = useState(initialColor);

    const [selectedColor, selectedColorMeta, selectedColorHelpers] = useField({
        name: props.variableID,
        onBlur: props.inputProps ? props.inputProps.onBlur : undefined,
        onChange: props.inputProps ? props.inputProps.onChange : undefined,
        value: colorOut(initialColor),
    });

    const isValidColorString = (color: string) => {
        const Validator = Collit.Validator;
        return (
            typeof color === "string" &&
            (Validator.isHex(color) ||
                Validator.isRgb(color) ||
                Validator.isRgba(color) ||
                Validator.isHsl(color) ||
                Validator.isHsla(color))
        );
    };

    const clickReadInput = () => {
        if (colorInput && colorInput.current) {
            colorInput.current.click();
        }
    };

    const onTextInputChange = e => {
        console.log("isValidColorString(e.target.value): ", isValidColorString(e.target.value));
        if (isValidColorString(e.target.value)) {
            const newColor = color(e.target.value);
            selectedColorHelpers.setValue(newColor);
            setLastValidColor(newColor);
            setInputTextValue(newColor);
        }

        return e.target.value;
    };

    const onColorInputChange = e => {
        const newColor = color(e.target.value);
        selectedColorHelpers.setValue(newColor);
        setLastValidColor(newColor);
        setInputTextValue(newColor);
    };

    const formattedColor = colorOut(lastValidColor);

    return (
        <>
            <span className={classes.root}>
                {/*Text Input*/}
                <input
                    ref={textInput}
                    type="text"
                    id={props.inputID}
                    aria-describedby={props.labelID}
                    className={classes.textInput}
                    placeholder={"#0291DB"}
                    value={colorOut(inputTextValue)}
                    onChange={onTextInputChange}
                />
                {/*"Real" color input*/}
                <input
                    {...props.inputProps}
                    ref={colorInput}
                    type="color"
                    id={props.inputID}
                    aria-describedby={props.labelID}
                    className={classNames(classes.realInput, visibility().visuallyHidden)}
                    onChange={onColorInputChange}
                />
                {/*Swatch*/}
                <span
                    onClick={clickReadInput}
                    style={{ backgroundColor: formattedColor }}
                    title={formattedColor}
                    aria-hidden={true}
                    className={classes.swatch}
                    tabIndex={-1}
                >
                    <span className={visibility().visuallyHidden}>{formattedColor}</span>
                </span>
            </span>
        </>
    );
}
