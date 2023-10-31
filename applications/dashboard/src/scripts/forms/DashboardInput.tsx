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

interface IProps extends IInputTextProps {
    errors?: IFieldError[];
    afterInput?: React.ReactNode;
}

export const DashboardInput: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType } = useFormGroup();

    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
            <InputTextBlock
                id={inputID}
                inputProps={props.inputProps}
                multiLineProps={props.multiLineProps}
                className={cx(props.inputProps ? props.inputProps.className : null, props.className)}
                noMargin={true}
            />
            {props.errors && <ErrorMessages errors={props.errors} />}
            {props.afterInput}
        </div>
    );
};
