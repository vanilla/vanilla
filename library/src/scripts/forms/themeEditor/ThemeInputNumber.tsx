/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useRef, useState, useReducer, useCallback } from "react";
import classNames from "classnames";
import { useField } from "formik";
import { t } from "@vanilla/i18n/src";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { getDefaultOrCustomErrorMessage, isValidColor } from "@library/styles/styleUtils";
import { themeInputNumberClasses } from "@library/forms/themeEditor/ThemeInputNumber.styles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useInterval } from "@vanilla/react-utils";

type IErrorWithDefault = string | boolean; // Uses default message if true

interface IProps {
    inputProps?: Omit<React.HTMLAttributes<HTMLInputElement>, "type" | "id" | "tabIndex" | "step" | "min" | "max">;
    inputID: string;
    variableID: string;
    labelID: string;
    defaultValue?: number;
    placeholder?: number;
    inputClass?: string;
    errors?: IErrorWithDefault[]; // Uses default message if true
    step?: number;
    min?: number;
    max?: number;
    errorMessage?: string;
}

enum StepAction {
    INCR = "incr",
    DECR = "decr",
}

export function ThemeInputNumber(props: IProps) {
    const classes = themeInputNumberClasses();
    const textInput = useRef<HTMLInputElement>(null);
    const builderClasses = themeBuilderClasses();
    const errorMessage = getDefaultOrCustomErrorMessage(props.errorMessage, t("Invalid Number"));

    const { step = 1, min = 0, max } = props;

    const validatedStep = Number.isInteger(step) ? step : 1;
    const validatedMin = Number.isInteger(min) ? min : 0;
    const validatedMax = max && Number.isInteger(max) ? max : undefined;

    /**
     * Check if is valid number, respecting parameters.
     * @param number
     */
    const isValidValue = (numberVal: number | string) => {
        if (numberVal !== undefined && Number.isInteger(ensureInteger(numberVal))) {
            const validatedNumber = parseInt(numberVal.toString());
            return (
                validatedNumber % validatedStep === 0 &&
                validatedNumber >= min &&
                (!validatedMax ? validatedNumber <= validatedMax! : true)
            );
        }
        return false;
    };

    const errorID = useMemo(() => {
        return uniqueIDFromPrefix("inputNumberError");
    }, []);

    const ensureInteger = (val: number | string) => {
        return parseInt(val.toString());
    };

    const [number, numberMeta, helpers] = useField(props.variableID);
    const [errorField, errorMeta, errorHelpers] = useField("errors." + props.variableID);

    const onTextChange = e => {
        helpers.setTouched(true);
        let newVal = e.target.value;
        if (Number.isInteger(newVal)) {
            newVal = ensureInteger(newVal);
            helpers.setValue(newVal);
            errorHelpers.setValue(false);
        } else {
            e.preventDefault();
        }
    };

    const [internalCount, dispatch] = useReducer((state: number, action: StepAction) => {
        switch (action) {
            case StepAction.DECR: {
                const value = Math.max(props.min ?? 0, state - 1);
                helpers.setValue(value);
                return value;
            }
            case StepAction.INCR: {
                const value = Math.min(props.max ?? 100000, state + 1);
                helpers.setValue(value);
                return value;
            }
        }
    }, props.defaultValue ?? 0);

    const stepUp = useCallback(() => dispatch(StepAction.INCR), []);
    const stepDown = useCallback(() => dispatch(StepAction.DECR), []);

    const stepUpIntervalProps = usePressInterval(stepUp);
    const stepDownIntervalProps = usePressInterval(stepDown);

    const hasError = number.value ? !!errorField.value || (!isValidValue(number.value) && number.value === "") : false;

    // Check initial value for errors
    useEffect(() => {
        if (number.value === undefined) {
            if (props.defaultValue !== undefined && Number.isInteger(ensureInteger(props.defaultValue))) {
                helpers.setValue(props.defaultValue);
            } else {
                helpers.setValue("");
            }
        }
    }, []);

    // Check initial value for errors
    useEffect(() => {
        if (hasError) {
            helpers.setError(true);
        } else {
            helpers.setError(false);
        }
    }, []);

    return (
        <>
            <span className={classes.root}>
                <input
                    ref={textInput}
                    type="number"
                    aria-describedby={props.labelID}
                    aria-hidden={true}
                    className={classNames(classes.textInput, {
                        [builderClasses.invalidField]: hasError,
                    })}
                    placeholder={props.placeholder ? props.placeholder.toString() : ""}
                    value={number.value || ""}
                    onChange={onTextChange}
                    auto-correct="false"
                    step={validatedStep}
                    min={validatedMin}
                    max={validatedMax}
                />
                <span className={classes.spinner}>
                    <span className={classes.spinnerSpacer}>
                        <Button
                            onClick={stepUp}
                            {...stepUpIntervalProps}
                            disabled={!max && number.value && number.value >= max!}
                            className={classes.stepUp}
                            baseClass={ButtonTypes.CUSTOM}
                        >
                            +
                        </Button>
                        <Button
                            onClick={stepDown}
                            {...stepDownIntervalProps}
                            disabled={number.value === min}
                            className={classes.stepDown}
                            baseClass={ButtonTypes.CUSTOM}
                        >
                            -
                        </Button>
                    </span>
                </span>
            </span>
            {hasError && (
                <ul id={errorID} className={builderClasses.errorContainer}>
                    <li className={builderClasses.error}>{errorMessage}</li>
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
