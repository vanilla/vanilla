/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ISelectLookupProps, SelectLookup } from "@library/forms/select/SelectLookup";
import classNames from "classnames";
import React from "react";

interface IProps extends Omit<ISelectLookupProps, "label" | "labelID" | "inputID"> {}

export const DashboardSelectLookup: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelID } = useFormGroup();
    return (
        <DashboardInputWrap>
            <SelectLookup
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
