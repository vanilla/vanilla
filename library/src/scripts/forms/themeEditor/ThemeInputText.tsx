/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useCallback, useEffect, useState } from "react";
import { useThemeVariableField } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import InputTextBlock from "@library/forms/InputTextBlock";
import debounce from "lodash/debounce";
import { themeInputTextClasses } from "@library/forms/themeEditor/themeInputText.styles";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";

interface IProps {
    debounceTime?: boolean | number;
    varKey: string;
    validation?: (newValue: string) => boolean;
    errorMessage?: string;
    forceError?: boolean;
}

export function ThemeInputText(props: IProps) {
    const {
        varKey,
        validation = () => {
            return true; // always valid if no function is defined
        },
        errorMessage,
        forceError,
    } = props;
    const classes = themeInputTextClasses();

    const hasDebounce = !!props.debounceTime;
    const debounceTime = typeof props.debounceTime === "number" ? props.debounceTime : props.debounceTime ? 10 : 0;

    const { generatedValue, defaultValue, setValue } = useThemeVariableField(varKey);

    const [valid, setValid] = useState(true);
    const [focus, setFocus] = useState(false);

    // initial value
    useEffect(() => {
        setValid(validation(generatedValue));
    }, [focus]);

    // Debounced internal function for input text.
    const _debounceInput = useCallback(
        debounce(
            (newValue: string) => {
                setValue(newValue);
            },
            debounceTime,
            { trailing: true, leading: true },
        ),
        [],
    );

    const errors = [{ message: errorMessage || t("Error") }] as IError[];
    const showError = forceError || (!valid && !focus);

    return (
        <span className={classNames(classes.root, { hasError: showError })}>
            <InputTextBlock
                errors={showError ? errors : undefined}
                inputProps={{
                    defaultValue: defaultValue,
                    className: classes.input,
                    value: generatedValue,
                    onFocus: () => {
                        setFocus(true);
                    },
                    onBlur: () => {
                        setFocus(false);
                    },
                    onChange: event => {
                        const newValue = event.target.value;
                        hasDebounce
                            ? _debounceInput(newValue)
                            : () => {
                                  setValue(newValue);
                                  setValid(validation(newValue));
                              };
                    },
                }}
            />
        </span>
    );
}
