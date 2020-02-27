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
import { useField } from "formik";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n/src";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import { isValidInteger } from "@library/styles/styleUtils";
import { string } from "prop-types";

type IErrorWithDefault = string | boolean; // Uses default message if true

export interface IInputNumber {
    inputProps?: Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex">;
    inputID: string;
    variableID: string;
    labelID: string;
    defaultValue?: ColorHelper;
    inputClass?: string;
    errors?: IErrorWithDefault[]; // Uses default message if true
    step?: number;
    min?: number;
    max?: number;
}



export default function NumberInput(props: IInputNumber) {
    const classes = colorPickerClasses();
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();

    const {step = 1, min = 0, max} = props;

    const validatedStep = isValidInteger(step) ? step : 1;
    const validatedMin = isValidInteger(min) ? min : 0;
    const validatedMax = max;

    /**
     * Check if is valid number, respecting parameters.
     * @param number
     */
    const isValidValue = (number) => {
        if (isValidInteger(number)) {

        } else {
            return false;
        }
    }

    const errorID = useMemo(() => {
        return uniqueIDFromPrefix("numberInputError");
    }, []);

    // String
    const initialValidNumber =
        props.defaultValue && isValidColor(props.defaultValue.toString()) ? props.defaultValue.toString() : "#000";

    const [selectedColor, selectedColorMeta, helpers] = useField(props.variableID);
    const [validColor, setValidColor] = useState(initialValidNumber);

    const onTextChange = e => {
        const number = e.target.value;
        helpers.setTouched(true);
        if () {
            setValidNumber(number); // Only set valid color if passes validation
        }
        helpers.setValue(number); // Text is unchanged
    };

    const textValue =
        selectedColor.value !== undefined
            ? selectedColor.value
            : props.defaultValue && isValidColor(props.defaultValue)
            ? props.defaultValue.toHexString()
            : "";
    const hasError = !isValidColor(textValue);

    return (
        <>
            <span className={classes.root}>
                <input
                    ref={textInput}
                    type="number"
                    aria-describedby={props.labelID}
                    aria-hidden={true}
                    className={classNames(classes.textInput, {
                        [classes.invalidColor]: hasError,
                    })}
                    placeholder={"#0291DB"}
                    value={textValue}
                    onChange={onTextChange}
                    auto-correct="false"
                />
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
