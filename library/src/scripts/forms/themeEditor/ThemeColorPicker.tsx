/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { colorPickerClasses } from "@library/forms/themeEditor/ThemeColorPicker.styles";
import { ensureColorHelper } from "@library/styles/styleHelpers";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { stringIsValidColor } from "@library/styles/styleUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import debounce from "lodash/debounce";
import React, { useCallback, useEffect, useRef, useState } from "react";

interface IProps extends Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex"> {
    variableKey: string;
    inputClass?: string;
}

export function ThemeColorPicker(_props: IProps) {
    const { variableKey, inputClass, ...inputProps } = _props;
    const { inputID, labelID } = useThemeBlock();

    // The field
    const { generatedValue, rawValue, defaultValue, setValue, error, setError } = useThemeVariableField(variableKey);

    const classes = colorPickerClasses();
    const colorInput = useRef<HTMLInputElement>(null);
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();

    const errorID = useUniqueID("colorPickerError");

    // Track whether we have a valid color.
    // If the color is not set, we don't really care.
    const [textInputValue, setTextFieldValue] = useState<string | null>(rawValue);
    const [lastValidColor, setLastValidColor] = useState<string | null>(rawValue ?? null);

    // If we have no color selected we are displaying the default and are definitely valid.
    const isValidColor = textInputValue ? stringIsValidColor(textInputValue) : true;

    // Do initial load validation of the color.
    useEffect(() => {
        if (!isValidColor) {
            setError(t("Invalid Color"));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleColorChange = (colorString: string) => {
        setTextFieldValue(colorString);
        if (colorString === "") {
            // we are clearing our color to the default.
            setValue(colorString);
            setLastValidColor(defaultValue);
        } else if (stringIsValidColor(colorString)) {
            setValue(colorString); // Only set valid color if passes validation
            setLastValidColor(colorString);
        } else {
            setError(t("Invalid Color"));
        }
    };

    // Handle updates from the text field.
    const onTextChange = e => {
        const colorString = e.target.value;
        handleColorChange(colorString);
    };

    const handleColorChangeRef = useRef<typeof handleColorChange>(handleColorChange);

    // Debounced internal function for onPickerChange.
    // Be sure to always use it through the following ref so that we the function identitity,
    // While still preserving the debounce.
    // This article explains the issue being worked around here https://dmitripavlutin.com/react-hooks-stale-closures/
    const _debouncedPickerUpdate = useCallback(
        debounce(
            (colorString: string) => {
                handleColorChangeRef.current(colorString);
            },
            16,
            { trailing: true },
        ),
        [],
    );

    // Handle updates from the color picker.
    const onPickerChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        // Will always be valid color, since it's a real picker
        const newColor: string = e.target.value;
        if (newColor) {
            handleColorChangeRef.current = handleColorChange;
            _debouncedPickerUpdate(newColor);
        }
    };

    const clickReadInput = () => {
        if (colorInput && colorInput.current) {
            colorInput.current.click();
        }
    };

    const defaultColorString = ensureColorHelper(generatedValue).toHexString();
    const validColorString = lastValidColor ? ensureColorHelper(lastValidColor).toHexString() : defaultColorString;

    return (
        <>
            <span className={classes.root}>
                {/*Text Input*/}
                <input
                    ref={textInput}
                    type="text"
                    aria-describedby={labelID}
                    aria-hidden={true}
                    className={classNames(classes.textInput, {
                        [builderClasses.invalidField]: !!error,
                    })}
                    placeholder={defaultColorString}
                    value={textInputValue ?? ""} // Null is not an allowed value for an input.
                    onChange={onTextChange}
                    auto-correct="false"
                />

                {/* Hidden "Real" color input*/}
                <input
                    {...inputProps}
                    ref={colorInput}
                    type="color"
                    id={inputID}
                    aria-describedby={labelID}
                    className={classNames(classes.realInput, visibility().visuallyHidden)}
                    onChange={onPickerChange}
                    onBlur={onPickerChange}
                    aria-errormessage={error ? errorID : undefined}
                    defaultValue={defaultColorString}
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
            {error && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    <li className={builderClasses.error}>{error}</li>
                </ul>
            )}
        </>
    );
}
