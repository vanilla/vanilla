/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import InputTextBlock, { IInputTextProps } from "@library/forms/InputTextBlock";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { cx } from "@emotion/css";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";

interface IProps extends IInputTextProps {
    errors?: IFieldError[];
    afterInput?: React.ReactNode;
    nextToInput?: React.ReactNode;
}

export const DashboardInput: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType } = useFormGroup();
    const classes = dashboardClasses();
    const formStyle = useDashboardFormStyle();

    return (
        <DashboardInputWrap>
            <div className={cx(classes.inputWrapper, { isCompact: formStyle.compact })}>
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
        </DashboardInputWrap>
    );
};
