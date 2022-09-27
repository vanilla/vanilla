/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ensureColorHelper } from "@library/styles/styleHelpers";
import { stringIsValidColor } from "@library/styles/styleUtils";
import { t } from "@vanilla/i18n/src";
import debounce from "lodash/debounce";
import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Pickr from "@simonwep/pickr";
import { colorPickerClasses } from "@dashboard/components/ColorPicker.styles";
import "./ColorPicker.scss";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { cx } from "@emotion/css";
import { ClearButton } from "@library/forms/select/ClearButton";
import { globalVariables } from "@library/styles/globalStyleVars";

interface IProps extends Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex" | "onChange"> {
    rootClassName?: string;
    inputClassName?: string;
    swatchClassName?: string;
    disabled?: boolean;
    value?: string;
    onChange?: (color: string) => void;
    inputID?: string;
    labelID?: string;
    isInvalid?: (invalid: boolean) => void;
    placeholder?: string;
    defaultBackground?: string;
}

const PICKER_DEFAULT_BACKGROUND = "#037DBC";

export function ColorPicker(_props: IProps) {
    const {
        rootClassName,
        inputClassName,
        swatchClassName,
        disabled,
        value,
        onChange,
        inputID,
        labelID,
        isInvalid,
        placeholder,
        defaultBackground,
    } = _props;

    const classes = colorPickerClasses();
    const textInput = useRef<HTMLInputElement>(null);
    const defaultBackgroundColor =
        defaultBackground === "global-mainColors-fg"
            ? globalVariables().mainColors.fg.toHexString()
            : PICKER_DEFAULT_BACKGROUND;

    // Track whether we have a valid color.
    // If the color is not set, we don't really care.
    const [textInputValue, setTextFieldValue] = useState<string | null>(value ?? null);
    const [lastValidColor, setLastValidColor] = useState<string | null>(value ?? null);

    const [currentlySelectedColor, setCurrentlySelectedColor] = useState(value ?? null);

    useEffect(() => {
        setTextFieldValue(value ?? null);
        if (stringIsValidColor(value)) {
            setLastValidColor(value ?? null);
        }
        if (value == null) {
            setLastValidColor(null);
        }
    }, [value, setTextFieldValue]);

    const handleColorChange = (colorString: string) => {
        setTextFieldValue(colorString);
        if (colorString === "") {
            // we are clearing our color to the default.
            setCurrentlySelectedColor(colorString);
            onChange && onChange(colorString);
            setLastValidColor("");
        } else if (stringIsValidColor(colorString)) {
            setCurrentlySelectedColor(colorString); // Only set valid color if passes validation
            setLastValidColor(colorString);
            isInvalid && isInvalid(false);
            onChange && onChange(colorString);
        } else if (!stringIsValidColor(colorString)) {
            setCurrentlySelectedColor(t("Invalid Color"));
            isInvalid && isInvalid(true);
        }
    };

    // Handle updates from the text field.
    const onTextChange = (e) => {
        const colorString = e.target.value;
        handleColorChange(colorString);
    };

    const handleColorChangeRef = useRef<typeof handleColorChange>(handleColorChange);

    // Debounced internal function for onPickerChange.
    // Be sure to always use it through the following ref so that we the function identity,
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
        //in case its our default picker color, we need to make sure we actually want that color and its not just empty input
        if (newColor && (newColor !== defaultBackgroundColor || currentlySelectedColor === defaultBackgroundColor)) {
            handleColorChangeRef.current = handleColorChange;
            _debouncedPickerUpdate(newColor);
        }
    };

    const defaultColorString: string = currentlySelectedColor
        ? ensureColorHelper(currentlySelectedColor).toHexString()
        : defaultBackgroundColor;
    const validColorString: string = lastValidColor
        ? ensureColorHelper(lastValidColor).toHexString()
        : defaultColorString;

    return (
        <>
            <span className={cx(classes.root, rootClassName)}>
                <input
                    id={inputID}
                    ref={textInput}
                    type="text"
                    aria-describedby={labelID}
                    aria-hidden={true}
                    className={cx(inputClassName)}
                    placeholder={placeholder ?? defaultColorString}
                    value={textInputValue ?? ""} // Null is not an allowed value for an input.
                    onChange={onTextChange}
                    auto-correct="false"
                    disabled={disabled}
                    aria-disabled={disabled}
                />
                {textInputValue && (
                    <ClearButton className={classes.clearButton} onClick={(e) => handleColorChange("")} />
                )}
                <Picker
                    onChange={onPickerChange}
                    validColorString={validColorString}
                    swatchClassName={swatchClassName}
                />
            </span>
        </>
    );
}

function Picker(props: { onChange: (newColor: string) => void; validColorString: string; swatchClassName?: string }) {
    const ref = useRef<HTMLButtonElement>(null);
    const pickrRef = useRef<Pickr | null>(null);
    const { onChange, validColorString, swatchClassName } = props;
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
            className={cx(classes.swatch, swatchClassName)}
            tabIndex={-1}
            buttonType={ButtonTypes.CUSTOM}
        >
            <ScreenReaderContent>{props.validColorString}</ScreenReaderContent>
        </Button>
    );
}
