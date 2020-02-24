/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import { colorPickerClasses } from "@library/forms/themeEditor/colorPickerStyles";
import { ColorHelper } from "csx";
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
    const inputRef = useRef<HTMLInputElement>(null);
    const [selectedColor, selectedColorMeta, selectedColorHelpers] = useField({
        name: props.variableID,
        onBlur: props.inputProps ? props.inputProps.onBlur : undefined,
        onChange: props.inputProps ? props.inputProps.onChange : undefined,
        value: props.initialColor ? colorOut(props.initialColor) : "#000",
    });
    const clickReadInput = () => {
        if (inputRef && inputRef.current) {
            inputRef.current.click();
        }
    };

    const formattedColor = colorOut(selectedColorMeta.value);

    return (
        <span className={classes.root}>
            <input
                {...props.inputProps}
                ref={inputRef}
                type="color"
                id={props.inputID}
                aria-describedby={props.labelID}
                className={classNames(visibility().visuallyHidden, props.inputClass)}
            />
            <span
                onClick={clickReadInput}
                style={{ backgroundColor: formattedColor }}
                title={formattedColor}
                aria-hidden={true}
                className={classes.swatch}
                tabIndex={-1}
            >
                {formattedColor}
            </span>
        </span>
    );
}
