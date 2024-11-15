/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import ErrorMessages from "@library/forms/ErrorMessages";
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import classNames from "classnames";
import React from "react";

interface IProps extends Omit<ISelectOneProps, "inputID" | "labelID" | "label"> {}

export const DashboardSelect: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelID } = useFormGroup();
    return (
        <DashboardInputWrap>
            <SelectOne
                {...props}
                label={null}
                labelID={labelID}
                inputID={inputID}
                inputClassName={classNames("form-control", props.inputClassName)}
            />
            {props.errors && <ErrorMessages errors={props.errors} />}
        </DashboardInputWrap>
    );
};
