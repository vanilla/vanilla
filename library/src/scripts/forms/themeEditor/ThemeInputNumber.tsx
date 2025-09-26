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
import { themeInputNumberClasses } from "@library/forms/themeEditor/ThemeInputNumber.styles";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n/src";
import { useInterval, useLastValue } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useCallback, useEffect, useReducer, useState } from "react";
import { ThemeBuilderRevert } from "@library/forms/themeEditor/ThemeBuilderRevert";

interface IProps
    extends Omit<
        React.HTMLAttributes<HTMLInputElement>,
        "type" | "id" | "tabIndex" | "step" | "min" | "max" | "placeholder"
    > {
    variableKey: string;
    step?: number;
    min?: number;
    max?: number;
    disabled?: boolean;
    floatPrecision?: number;
}

enum StepAction {
    INCR = "incr",
    DECR = "decr",
}

export function ThemeInputNumber(_props: IProps) {
    const builderClasses = themeBuilderClasses();
    const { step = 1, floatPrecision = 0, min = 0, max, variableKey, ...inputProps } = _props;

    const { rawValue, generatedValue, error, setError, setValue } = useThemeVariableField<number | string>(variableKey);
    const ensureInteger = (val: number | string): number => {
        if (floatPrecision) {
            const floatVal = parseFloat(val?.toString());
            return floatVal;
        }
        return parseInt(val?.toString());
    };
    const intValue = ensureInteger(rawValue ?? generatedValue ?? 0);
    const lastIntValue = useLastValue(intValue);
    const [textValue, setTextValue] = useState<string>(
        floatPrecision > 0 ? intValue.toFixed(floatPrecision) : intValue.toString(),
    );

    useEffect(() => {
        if (intValue !== lastIntValue) {
            updateValue(textValue);
        }
    }, [intValue, lastIntValue]);

    function updateValue(newValue: string | number, rerenderText: boolean = false) {
        if (newValue === "") {
            setTextValue("");
            setError(null);
            return;
        }
        try {
            const newIntVal = validateNumber(newValue);
            if (rerenderText) {
                setTextValue(floatPrecision > 0 ? newIntVal.toFixed(floatPrecision) : newIntVal.toString());
            } else {
                setTextValue(newValue.toString());
            }

            if (newIntVal !== intValue) {
                setValue(newIntVal);
                setError(null);
            }
        } catch (e) {
            setTextValue(newValue.toString());
            setError(e.message);
        }
    }

    /**
     * Check if is valid number, respecting parameters.
     */
    const validateNumber = (numberVal: number | string): number => {
        const intVal = ensureInteger(numberVal ?? "");
        if (numberVal !== undefined && !Number.isNaN(intVal)) {
            const overMin = intVal >= min;
            const underMax = !max || intVal <= max;

            if (!overMin) {
                throw new Error(t("Too Small"));
            } else if (!underMax) {
                throw new Error(t("Too Large"));
            }
            return intVal;
        } else {
            throw new Error(floatPrecision ? t("Invalid Number") : t("Invalid Integer"));
        }
    };

    const handleTextChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        updateValue(e.target.value);
    };

    const [_, dispatch] = useReducer((state: number, action: StepAction) => {
        switch (action) {
            case StepAction.DECR: {
                const value = Math.max(min ?? 0, state - step);
                updateValue(value, true);

                return value;
            }
            case StepAction.INCR: {
                const value = max !== undefined ? Math.min(max ?? 100000, state + step) : state + step;
                updateValue(value, true);
                return value;
            }
        }
    }, intValue);

    const stepUp = useCallback(() => dispatch(StepAction.INCR), []);
    const stepDown = useCallback(() => dispatch(StepAction.DECR), []);

    const stepUpIntervalProps = usePressInterval(stepUp);
    const stepDownIntervalProps = usePressInterval(stepDown);

    const errorID = useUniqueID("inputNumberError");
    const { labelID, inputID } = useThemeBlock();
    const classes = themeInputNumberClasses();

    return (
        <>
            <span className={classes.root}>
                <input
                    onBlur={() => {
                        if (textValue === "") {
                            setValue(undefined);
                            setError(null);
                        }
                    }}
                    {...inputProps}
                    type="number"
                    id={inputID}
                    aria-describedby={labelID}
                    className={classNames(classes.textInput, {
                        [builderClasses.invalidField]: !!error,
                    })}
                    placeholder={String(generatedValue)}
                    value={textValue}
                    onChange={handleTextChange}
                    autoCorrect="false"
                    step={step}
                    min={min}
                    max={max}
                    disabled={inputProps.disabled}
                    aria-disabled={inputProps.disabled}
                />
                <span className={classes.spinner}>
                    <span className={classes.spinnerSpacer}>
                        <Button
                            onClick={stepUp}
                            {...stepUpIntervalProps}
                            disabled={
                                inputProps.disabled || (max != undefined && ensureInteger(generatedValue!) >= max)
                            }
                            className={classes.stepUp}
                            buttonType={ButtonTypes.CUSTOM}
                        >
                            +
                        </Button>
                        <Button
                            onClick={stepDown}
                            {...stepDownIntervalProps}
                            disabled={inputProps.disabled || ensureInteger(generatedValue!) <= min}
                            className={classes.stepDown}
                            buttonType={ButtonTypes.CUSTOM}
                        >
                            -
                        </Button>
                    </span>
                </span>
                <ThemeBuilderRevert variableKey={variableKey} />
            </span>
            {error && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    <li className={builderClasses.error}>{error}</li>
                </ul>
            )}
        </>
    );
}

function usePressInterval(callback: () => void) {
    const [isHolding, setIsHolding] = useState(false);

    const onMouseDown = (event: React.MouseEvent) => {
        setIsHolding(true);
    };

    const onMouseUp = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        setIsHolding(false);
    };

    useInterval(
        () => {
            if (isHolding) {
                callback();
            }
        },
        isHolding ? 120 : null,
    );

    return { onMouseDown, onMouseUp };
}
