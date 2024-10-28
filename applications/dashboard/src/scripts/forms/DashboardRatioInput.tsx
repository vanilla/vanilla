/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { cx } from "@emotion/css";
import InputTextBlock, { IInputProps } from "@library/forms/InputTextBlock";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { t } from "@library/utility/appUtils";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { IOptionalComponentID } from "@library/utility/idUtils";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";

interface IRatioInputProps extends IOptionalComponentID, Omit<IInputProps, "value" | "onChange"> {
    value: number;
    onChange: (value: number) => void;
}

const DEFAULT_DENOMINATOR = 1;

function RatioInput(props: IRatioInputProps) {
    const { id, value = DEFAULT_DENOMINATOR, onChange, className, ...rest } = props;

    const classes = dashboardClasses();

    return (
        <>
            {/* The first number in the ratio is always 1, it's not editable by the user */}
            <span>{"1"}</span>

            <span>{t("in")}</span>

            <div className={classes.ratioInput}>
                <InputTextBlock
                    id={id}
                    inputProps={{
                        ...rest,
                        type: "number",
                        inputmode: "numeric",
                        min: 1,
                        step: 1,
                        pattern: "[0-9]*",
                        value: value,
                        onChange: (event) => {
                            const newValue = parseInt(event.target.value);
                            props.onChange(isNaN(newValue) || newValue === 0 ? DEFAULT_DENOMINATOR : newValue);
                        },
                    }}
                    className={className}
                    noMargin={true}
                />
            </div>
        </>
    );
}

export default function DashboardRatioInput(
    props: IRatioInputProps & {
        errors?: IFieldError[];
    },
) {
    const { className, errors, ...rest } = props;
    const { inputID } = useFormGroup();
    const classes = dashboardClasses();

    return (
        <DashboardInputWrap className={className}>
            <div className={classes.ratioInputContainer}>
                <RatioInput {...rest} id={inputID} />
            </div>
            {props.errors && <ErrorMessages errors={props.errors} />}
        </DashboardInputWrap>
    );
}
