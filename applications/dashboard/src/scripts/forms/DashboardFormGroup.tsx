/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { useUniqueID } from "@library/utility/idUtils";
import { DashboardFormLabel, DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import classNames from "classnames";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { FormGroupContext } from "./DashboardFormGroupContext";

interface IProps {
    label: string;
    description?: React.ReactNode;
    afterDescription?: React.ReactNode;
    labelType?: DashboardLabelType;
    tag?: keyof JSX.IntrinsicElements;
    children: React.ReactNode;
    isIndependant?: boolean; // Setting this resets the side margins.
}

export function DashboardFormGroup(props: IProps) {
    const Tag = (props.tag || "li") as "li";
    const inputID = useUniqueID("formGroup-");
    const labelID = inputID + "-label";

    return (
        <Tag className={classNames("form-group", props.isIndependant && "row")}>
            <FormGroupContext.Provider
                value={{ inputID, labelID, labelType: props.labelType || DashboardLabelType.STANDARD }}
            >
                <DashboardFormLabel {...props} />
                {props.children}
            </FormGroupContext.Provider>
        </Tag>
    );
}
