/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";

interface IProps extends React.InputHTMLAttributes<HTMLInputElement> {}

export const DashboardInput: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType } = useFormGroup();
    const classes = classNames("form-control", props.className);

    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
            <input type="text" {...props} id={inputID} className={classes} />
        </div>
    );
};
