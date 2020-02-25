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
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";

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

    const [lastValidColor, setLastValidColor] = useState(initialColor); // Always "Color" object
    const [inputTextValue, setInputTextValue] = useState(initialColor.toString()); // Always string

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
                    style={{ backgroundColor: formattedColor }}
                    title={formattedColor}
                    aria-hidden={true}
                    className={classes.swatch}
                    tabIndex={-1}
                    baseClass={ButtonTypes.CUSTOM}
                >
                    <span className={visibility().visuallyHidden}>{formattedColor}</span>
                </Button>
            </span>
        </>
    );
}
