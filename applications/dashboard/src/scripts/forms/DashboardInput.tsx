/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import InputTextBlock, { IInputTextProps } from "@library/forms/InputTextBlock";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";

interface IProps extends IInputTextProps {
    errors?: IFieldError[];
}

export const DashboardInput: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType } = useFormGroup();
    const classes = classNames(props.className);
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
            <InputTextBlock
                id={inputID}
                inputProps={props.inputProps}
                multiLineProps={props.multiLineProps}
                className={classNames(props.inputProps ? props.inputProps.className : null, classes)}
                noMargin={true}
            />
            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
};
