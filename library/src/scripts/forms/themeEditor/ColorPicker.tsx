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

    const [selectedColor, selectedColorMeta, selectedColorHelpers] = useField({
        name: props.variableID,
        onBlur: props.inputProps ? props.inputProps.onBlur : undefined,
        onChange: props.inputProps ? props.inputProps.onChange : undefined,
        value: colorOut(initialColor),
    });

    const clickReadInput = () => {
        if (colorInput && colorInput.current) {
            colorInput.current.click();
        }
    };

    const onTextInputChange = e => {
        const newColor = color(e.target.value);
        console.log("e.target.value: ", e.target.value);
        console.log("New Color: ", newColor);
        if (newColor) {
            selectedColorHelpers.setValue(newColor);
            setLastValidColor(newColor);
        }

        return e.target.value;
    };

    const onColorInputChange = e => {
        const newColor = color(e.target.value);
        if (newColor) {
            selectedColorHelpers.setValue(newColor);
            setLastValidColor(newColor);
        }
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
                    value={selectedColorMeta.value}
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
