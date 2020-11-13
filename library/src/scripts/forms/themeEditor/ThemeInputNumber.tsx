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
import { useInterval } from "@vanilla/react-utils";
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
}

enum StepAction {
    INCR = "incr",
    DECR = "decr",
}

export function ThemeInputNumber(_props: IProps) {
    const builderClasses = themeBuilderClasses();
    const { step = 1, min = 0, max, variableKey, ...inputProps } = _props;

    const { rawValue, generatedValue, error, setError, setValue } = useThemeVariableField<number | string>(variableKey);

    const ensureInteger = (val: number | string) => {
        return parseInt(val?.toString());
    };
    /**
     * Check if is valid number, respecting parameters.
     * @param number
     */
    const isValidValue = (numberVal: number | string, shouldThrow: boolean = false) => {
        const intVal = ensureInteger(numberVal ?? "");
        if (numberVal !== undefined && Number.isInteger(intVal)) {
            const validStep = intVal % step === 0;
            const overMin = intVal >= min;
            const underMax = !max || intVal <= max;
            const result = validStep && overMin && underMax;
            if (shouldThrow) {
                if (!validStep) {
                    return t("Invalid Step");
                } else if (!overMin) {
                    return t("Too Small");
                } else if (!underMax) {
                    return t("Too Large");
                }
            }

            return result;
        }
        return false;
    };

    const handleTextChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const intVal = ensureInteger(e.target.value);
        if (Number.isInteger(intVal)) {
            setValue(intVal);
        } else if (e.target.value === "") {
            // Clearing the input is always allowed.
            setValue("");
        } else {
            e.preventDefault();
        }
    };

    const [_, dispatch] = useReducer((state: number, action: StepAction) => {
        switch (action) {
            case StepAction.DECR: {
                const value = Math.max(min ?? 0, state - step);
                setValue(value);
                return value;
            }
            case StepAction.INCR: {
                const value = max !== undefined ? Math.min(max ?? 100000, state + step) : state + step;
                setValue(value);
                return value;
            }
        }
    }, ensureInteger(rawValue ?? generatedValue ?? 0));

    const stepUp = useCallback(() => dispatch(StepAction.INCR), []);
    const stepDown = useCallback(() => dispatch(StepAction.DECR), []);

    const stepUpIntervalProps = usePressInterval(stepUp);
    const stepDownIntervalProps = usePressInterval(stepDown);

    // Check initial value for errors
    useEffect(() => {
        if (generatedValue == null) {
            // No errors if we have a nullish value.
            return;
        }
        try {
            isValidValue(generatedValue);
        } catch (e) {
            setError(e.message);
        }
        // Initial value only
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const errorID = useUniqueID("inputNumberError");
    const { labelID, inputID } = useThemeBlock();
    const classes = themeInputNumberClasses();

    return (
        <>
            <span className={classes.root}>
                <input
                    {...inputProps}
                    type="number"
                    id={inputID}
                    aria-describedby={labelID}
                    className={classNames(classes.textInput, {
                        [builderClasses.invalidField]: !!error,
                    })}
                    placeholder={String(generatedValue)}
                    value={rawValue ?? ""}
                    onChange={handleTextChange}
                    auto-correct="false"
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
                            baseClass={ButtonTypes.CUSTOM}
                        >
                            +
                        </Button>
                        <Button
                            onClick={stepDown}
                            {...stepDownIntervalProps}
                            disabled={inputProps.disabled || ensureInteger(generatedValue!) <= min}
                            className={classes.stepDown}
                            baseClass={ButtonTypes.CUSTOM}
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
