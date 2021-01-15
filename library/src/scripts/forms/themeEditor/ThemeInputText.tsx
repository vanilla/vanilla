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
    allowEmpty?: true;
    placeholder?: string;
}

export function ThemeInputText(props: IProps) {
    const {
        varKey,
        validation = () => {
            return true; // always valid if no function is defined
        },
        errorMessage,
        forceError,
        allowEmpty,
    } = props;
    const classes = themeInputTextClasses();

    const debounceTime = typeof props.debounceTime === "number" ? props.debounceTime : props.debounceTime ? 250 : 0;

    const { generatedValue, defaultValue, setValue } = useThemeVariableField<string>(varKey);

    const [valid, setValid] = useState(true);
    const [focus, setFocus] = useState(false);

    // initial value
    useEffect(() => {
        setValid(validation(generatedValue!));
    }, [focus, generatedValue]);

    // Debounced internal function for input text.
    const _debounceInput = useCallback(
        debounce(
            (newValue: string) => {
                if (allowEmpty) {
                    setValue(newValue, true);
                } else {
                    setValue(newValue);
                }
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
                    placeholder: props.placeholder,
                    autoComplete: false,
                    defaultValue: defaultValue ?? undefined,
                    className: classes.input,
                    value: generatedValue ?? undefined,
                    onFocus: () => {
                        setFocus(true);
                    },
                    onBlur: () => {
                        setFocus(false);
                    },
                    onChange: (event) => {
                        _debounceInput(event.target.value);
                    },
                }}
            />
        </span>
    );
}
