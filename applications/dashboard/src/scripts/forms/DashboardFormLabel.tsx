/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";

interface IProps {
    label: string;
    description?: React.ReactNode;
    afterDescription?: React.ReactNode;
    labelType?: DashboardLabelType;
}

export enum DashboardLabelType {
    STANDARD = "standard",
    WIDE = "wide",
}

export const DashboardFormLabel: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelID } = useFormGroup();

    const labelType = props.labelType !== undefined ? props.labelType : DashboardLabelType.STANDARD;
    const rootClass = labelType === DashboardLabelType.WIDE ? "label-wrap-wide" : "label-wrap";

    return (
        <div className={rootClass} id={labelID}>
            {props.label && <label htmlFor={inputID}>{props.label}</label>}
            {props.description && <div className="info">{props.description}</div>}
            {props.afterDescription}
        </div>
    );
};
