/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType } from "react";
import { useUniqueID } from "@library/utility/idUtils";
import { DashboardFormLabel, DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { FormGroupContext } from "./DashboardFormGroupContext";
import { cx } from "@emotion/css";

interface IProps {
    label: React.ReactNode;
    description?: React.ReactNode;
    afterDescription?: React.ReactNode;
    labelType?: DashboardLabelType;
    inputType?: string;
    tag?: ElementType;
    children: React.ReactNode;
    isIndependant?: boolean; // Setting this resets the side margins.
    tooltip?: string;
    inputID?: string;
}

export function DashboardFormGroup(props: IProps) {
    const Tag = (props.tag || "li") as "li";
    const uniqueID = useUniqueID("formGroup-");
    const inputID = props.inputID ?? uniqueID;
    const labelID = inputID + "-label";

    return (
        <Tag
            className={cx(
                "form-group",
                { ["row"]: !!props.isIndependant },
                { [`formGroup-${props.inputType}`]: !!props.inputType },
            )}
        >
            <FormGroupContext.Provider
                value={{ inputID, labelID, labelType: props.labelType || DashboardLabelType.STANDARD }}
            >
                <DashboardFormLabel {...props} />
                {props.children}
            </FormGroupContext.Provider>
        </Tag>
    );
}
