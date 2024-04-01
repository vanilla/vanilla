/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
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

export const DashboardInput: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType } = useFormGroup();
    const classes = dashboardClasses();
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={cx(rootClass, props.className)}>
            <div className={classes.inputWrapper}>
                <InputTextBlock
                    id={inputID}
                    inputProps={props.inputProps}
                    multiLineProps={props.multiLineProps}
                    className={cx(props.inputProps ? props.inputProps.className : null, props.className)}
                    noMargin={true}
                />
                {props.nextToInput}
            </div>
            {props.errors && <ErrorMessages errors={props.errors} />}
            {props.afterInput}
        </div>
    );
};
