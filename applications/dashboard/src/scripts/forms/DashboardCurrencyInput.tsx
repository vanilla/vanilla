/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useEffect } from "react";
import InputTextBlock, { IInputTextProps } from "@library/forms/InputTextBlock";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { cx } from "@emotion/css";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

interface IProps extends IInputTextProps {
    errors?: IFieldError[];
    afterInput?: React.ReactNode;
    nextToInput?: React.ReactNode;
}

export const DashboardCurrencyInput: React.FC<IProps> = (props: IProps) => {
    const [value, setValue] = useState(props.inputProps?.value || "0.00");
    const classes = dashboardClasses();
    const rootClass = "input-wrap";

    function formatCurrency(value: string) {
        if (value === "" || value === "0.00") return "0.00";

        // The initial implemention for this component only supports dollars
        const intlFormattedArray = Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).formatToParts(parseFloat(value));

        // Remove the currency symbol and any non-decimal separators like commas
        const filteredValueArray = intlFormattedArray
            .filter((token) => token.type !== "currency" && token.type !== "group")
            .map((token) => token.value);

        return filteredValueArray.join("");
    }

    return (
        <div className={cx(rootClass, props.className)}>
            <div className={(classes.inputWrapper, classes.currencyInput)}>
                <span className="dollar">{"$"}</span>
                <InputTextBlock
                    inputProps={{
                        ...props.inputProps,
                        inputmode: "numeric",
                        min: 0,
                        step: 0.01,
                        pattern: "[0-9]*",
                        value: value,
                        onBlur: (event) => {
                            const newValue = formatCurrency(event.target.value);
                            setValue(newValue);
                            if (props.inputProps?.onBlur) {
                                props.inputProps.onBlur(event);
                            }
                        },
                        onChange: (event) => {
                            const newValue = event.target.value;
                            setValue(newValue);
                            if (props.inputProps?.onChange) {
                                props.inputProps.onChange(event);
                            }
                        },
                    }}
                    className={cx(props.inputProps ? props.inputProps.className : null, props.className)}
                    noMargin={true}
                />
            </div>
            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
};
