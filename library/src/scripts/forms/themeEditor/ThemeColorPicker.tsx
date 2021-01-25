/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { colorPickerClasses } from "@library/forms/themeEditor/ThemeColorPicker.styles";
import { ensureColorHelper } from "@library/styles/styleHelpers";
import { stringIsValidColor } from "@library/styles/styleUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import debounce from "lodash/debounce";
import React, { useCallback, useEffect, useRef, useState } from "react";
import { ThemeBuilderRevert } from "@library/forms/themeEditor/ThemeBuilderRevert";
import Pickr from "@simonwep/pickr";
import "./ThemeColorPicker.scss";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

interface IProps extends Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex"> {
    variableKey: string;
    inputClass?: string;
    disabled?: boolean;
}

export function ThemeColorPicker(_props: IProps) {
    const { variableKey, inputClass, disabled, ...inputProps } = _props;
    const { inputID, labelID } = useThemeBlock();

    // The field
    const { generatedValue, rawValue, defaultValue, setValue, error, setError } = useThemeVariableField<string>(
        variableKey,
    );

    const classes = colorPickerClasses();
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();

    const errorID = useUniqueID("colorPickerError");

    // Track whether we have a valid color.
    // If the color is not set, we don't really care.
    const [textInputValue, setTextFieldValue] = useState<string | null>(rawValue ?? null);
    const [lastValidColor, setLastValidColor] = useState<string | null>(rawValue ?? null);

    // If we have no color selected we are displaying the default and are definitely valid.
    const isValidColor = textInputValue ? stringIsValidColor(textInputValue) : true;

    useEffect(() => {
        setTextFieldValue(rawValue ?? null);
        if (stringIsValidColor(rawValue)) {
            setLastValidColor(rawValue ?? null);
        }
        if (rawValue == null) {
            setLastValidColor(null);
        }
    }, [rawValue, setTextFieldValue]);

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
            setLastValidColor(defaultValue ?? null);
        } else if (stringIsValidColor(colorString)) {
            setValue(colorString); // Only set valid color if passes validation
            setLastValidColor(colorString);
        } else {
            setError(t("Invalid Color"));
        }
    };

    // Handle updates from the text field.
    const onTextChange = (e) => {
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
    const onPickerChange = (newColor: string) => {
        // Will always be valid color, since it's a real picker
        if (newColor) {
            handleColorChangeRef.current = handleColorChange;
            _debouncedPickerUpdate(newColor);
        }
    };

    const defaultColorString = generatedValue ? ensureColorHelper(generatedValue).toHexString() : "#fff";
    const validColorString = lastValidColor ? ensureColorHelper(lastValidColor).toHexString() : defaultColorString;

    return (
        <>
            <span className={classes.root}>
                {/*Text Input*/}
                <input
                    id={inputID}
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
                    disabled={disabled}
                    aria-disabled={disabled}
                />
                <Picker onChange={onPickerChange} validColorString={validColorString} />
            </span>
            <ThemeBuilderRevert variableKey={variableKey} />
            {error && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    <li className={builderClasses.error}>{error}</li>
                </ul>
            )}
        </>
    );
}

function Picker(props: { onChange: (newColor: string) => void; validColorString: string }) {
    const ref = useRef<HTMLButtonElement>(null);
    const pickrRef = useRef<Pickr | null>(null);
    const { onChange, validColorString } = props;
    const classes = colorPickerClasses();
    const currentColorRef = useRef(validColorString);
    useEffect(() => {
        currentColorRef.current = validColorString;
    }, [validColorString]);

    const handleChange = useCallback(
        (color: Pickr.HSVaColor) => {
            const finalColor = color.toHEXA().toString();
            onChange(finalColor);
        },
        [onChange],
    );
    const changeHandlerRef = useRef<typeof handleChange | null>(null);

    useEffect(() => {
        if (changeHandlerRef.current) {
            pickrRef.current?.off("change", changeHandlerRef.current);
        }
        changeHandlerRef.current = handleChange;
        pickrRef.current?.on("change", handleChange);

        return () => {
            pickrRef.current?.off("change", handleChange);
        };
    }, [handleChange]);

    const createAndOpen = useCallback(() => {
        if (!ref.current) {
            return;
        }

        if (pickrRef.current) {
            return;
        }

        const pickr = Pickr.create({
            el: ref.current,
            theme: "nano",
            outputPrecision: 0,
            useAsButton: true,
            default: currentColorRef.current,
            components: {
                // Main components
                preview: true,
                hue: true,

                // Input / output Options
                interaction: {
                    input: true,
                },
            },
        });

        pickr.show();
        pickrRef.current = pickr;
        pickrRef.current?.on("change", handleChange);
        changeHandlerRef.current = handleChange;
    }, [handleChange]);

    useEffect(() => {
        return () => {
            pickrRef.current?.destroy();
            pickrRef.current = null;
        };
    }, []);

    useEffect(() => {
        pickrRef.current?.setColor(validColorString, true);
    }, [validColorString]);

    useEffect(() => {
        const handler = () => {
            if (document.activeElement?.tagName === "IFRAME") {
                pickrRef.current?.hide();
            }
        };
        window.addEventListener("blur", handler);
        return () => {
            window.removeEventListener("blur", handler);
        };
    }, []);

    return (
        <Button
            buttonRef={ref}
            onClick={() => {
                createAndOpen();
            }}
            style={{ backgroundColor: props.validColorString }}
            title={props.validColorString}
            aria-hidden={true}
            className={classes.swatch}
            tabIndex={-1}
            baseClass={ButtonTypes.CUSTOM}
        >
            <ScreenReaderContent>{props.validColorString}</ScreenReaderContent>
        </Button>
    );
}
