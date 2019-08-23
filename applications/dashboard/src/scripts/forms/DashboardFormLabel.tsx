/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";

interface IProps {
    label?: string;
    description?: string;
    labelType?: FormLabelType;
}

export enum FormLabelType {
    STANDARD = "",
    WIDE = "-wide",
    RIGHT = "-right",
}

export const DashboardFormLabel: React.FC<IProps> = (props: IProps) => {
    const { inputID } = useFormGroup();

    if (!props.label && !props.description) {
        return null;
    }

    const rootClass = "label-wrap" + (props.labelType !== undefined ? props.labelType : FormLabelType.STANDARD);

    return (
        <div className={rootClass}>
            {props.label && <label htmlFor={inputID}>{props.label}</label>}
            {props.description && <div className="info">{props.description}</div>}
        </div>
    );
};
