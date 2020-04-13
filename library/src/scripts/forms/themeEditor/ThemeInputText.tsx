/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useCallback, useEffect, useState } from "react";
import { useThemeVariableField } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import InputTextBlock from "@library/forms/InputTextBlock";
import debounce from "lodash/debounce";
import { themeInputTextClasses } from "@library/forms/themeEditor/themeInputText.styles";

interface IProps {
    debounceTime?: boolean | number;
    varKey: string;
    validation?: (newValue: string) => boolean;
    errorMessage?: string;
}

export function ThemeInputText(props: IProps) {
    const { varKey, validation, errorMessage } = props;
    const classes = themeInputTextClasses();

    const hasDebounce = !!props.debounceTime;
    const debounceTime = typeof props.debounceTime === "number" ? props.debounceTime : props.debounceTime ? 10 : 0;

    const { generatedValue, defaultValue, setValue } = useThemeVariableField(varKey);

    // const { generatedValue, initialValue } = useThemeVariableField(customFontUrlKey);
    const [valid, setValid] = useState(false);

    useEffect(() => {
        validation && setValid(validation(generatedValue));
        // setValid(generatedValue !== "" || urlValidation(generatedValue));
    }, [generatedValue]);

    // initial value
    useEffect(() => {
        validation && setValid(validation(generatedValue));
        // setValid(generatedValue !== "" || urlValidation(initialValue));
    }, []);

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

    return (
        <span className={classes.root}>
            <InputTextBlock
                errors={
                    valid || !errorMessage
                        ? undefined
                        : [
                              {
                                  message: errorMessage,
                              },
                          ]
                }
                inputProps={{
                    defaultValue: defaultValue,
                    className: classes.input,
                    value: generatedValue,
                    onChange: event => {
                        const newValue = event.target.value;
                        hasDebounce ? _debounceInput(newValue) : setValue(newValue);
                    },
                }}
            />
        </span>
    );
}
