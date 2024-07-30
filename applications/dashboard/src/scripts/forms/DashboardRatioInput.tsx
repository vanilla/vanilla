/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { cx } from "@emotion/css";
import InputTextBlock, { IInputTextProps } from "@library/forms/InputTextBlock";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { t } from "@library/utility/appUtils";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

interface IProps extends IInputTextProps {
    errors?: IFieldError[];
}

const DEFAULT_DENOMINATOR = 1;

export const DashboardRatioInput: React.FC<IProps> = (props: IProps) => {
    const [value, setValue] = useState(props.inputProps?.value || DEFAULT_DENOMINATOR);
    const classes = dashboardClasses();

    return (
        <div className={cx(props.className, classes.ratioInputContainer)}>
            {/* The first number in the ratio is always 1, it's not editable by the user */}
            <div className={classes.ratioInputReadOnlyNumerator}>{"1"}</div>

            <span className={classes.ratioInputSeparator}>{t("in")}</span>

            <div className={classes.ratioInput}>
                <InputTextBlock
                    inputProps={{
                        ...props.inputProps,
                        inputmode: "numeric",
                        min: 1,
                        step: 1,
                        pattern: "[0-9]*",
                        value: value,
                        onChange: (event) => {
                            const newValue = parseInt(event.target.value);

                            setValue(isNaN(newValue) || newValue === 0 ? DEFAULT_DENOMINATOR : newValue);
                            if (props.inputProps?.onChange) {
                                props.inputProps.onChange(event);
                            }
                        },
                    }}
                    noMargin={true}
                />
            </div>

            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
};
