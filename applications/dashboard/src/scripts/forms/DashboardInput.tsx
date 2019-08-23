/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";

interface IProps extends React.InputHTMLAttributes<HTMLInputElement> {}

export const DashboardInput: React.FC<IProps> = (props: IProps) => {
    const { inputID, hasLabel } = useFormGroup();
    const classes = classNames("form-control", props.className);

    return (
        <div className={classNames("input-wrap", { ["no-label"]: !hasLabel })}>
            <input type="text" {...props} id={inputID} className={classes} />
        </div>
    );
};
