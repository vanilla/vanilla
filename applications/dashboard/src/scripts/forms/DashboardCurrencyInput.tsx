/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useState } from "react";
import InputTextBlock, { IInputProps } from "@library/forms/InputTextBlock";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { IOptionalComponentID } from "@library/utility/idUtils";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";

interface ICurrencyInputProps extends IOptionalComponentID, Omit<IInputProps, "onChange"> {
    onChange: (value: string | number) => void;
    minimumFractionDigits?: number;
    maximumFractionDigits?: number;
}

function CurrencyInput(props: ICurrencyInputProps) {
    const { id, value, onChange, className, minimumFractionDigits, maximumFractionDigits, ...rest } = props;
    const classes = dashboardClasses();

    const [ownValue, setOwnValue] = useState(formatCurrency(`${value ?? ""}`));
    function formatCurrency(value: string) {
        if (value === "0.00" || value === "0") return value;
        if (value === "") {
            return maximumFractionDigits === 0 ? "0" : "0.00";
        }

        // The initial implemention for this component only supports dollars
        const intlFormattedArray = Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
            minimumFractionDigits: minimumFractionDigits ?? 2,
            maximumFractionDigits: maximumFractionDigits ?? 2,
            useGrouping: true,
        }).formatToParts(parseFloat(value));

        // Remove the currency symbol
        const filteredValueArray = intlFormattedArray
            .filter((token) => token.type !== "currency")
            .map((token) => token.value);

        return filteredValueArray.join("");
    }

    return (
        <div className={classes.currencyInput}>
            <span className={classes.currencySymbol}>{"$"}</span>
            <InputTextBlock
                id={id}
                inputProps={{
                    ...rest,
                    value: ownValue,
                    onBlur: (event) => {
                        setOwnValue(formatCurrency(`${value ?? ""}`));
                        props.onBlur?.(event);
                    },

                    onChange: (event) => {
                        const newValue = event.target.value;
                        setOwnValue(newValue);
                        onChange(newValue);
                    },
                }}
                className={className}
                noMargin={true}
            />
        </div>
    );
}

export default function DashboardCurrencyInput(
    props: ICurrencyInputProps & {
        errors?: IFieldError[];
    },
) {
    const { className, errors, ...rest } = props;
    const { inputID } = useFormGroup();

    return (
        <DashboardInputWrap className={className}>
            <CurrencyInput {...rest} id={inputID} />
            {props.errors && <ErrorMessages errors={props.errors} />}
        </DashboardInputWrap>
    );
}
