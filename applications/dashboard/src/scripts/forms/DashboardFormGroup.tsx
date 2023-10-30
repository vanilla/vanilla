/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType } from "react";
import { useUniqueID } from "@library/utility/idUtils";
import { DashboardFormLabel, DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { FormGroupContext } from "./DashboardFormGroupContext";
import { cx } from "@emotion/css";
import { IFieldError } from "@vanilla/json-schema-forms";

interface IProps extends React.ComponentProps<typeof DashboardFormLabel> {
    tag?: ElementType;
    children?: React.ReactNode;
    inputID?: string;
    className?: string;
    required?: boolean;
    errors?: IFieldError[];
}

export function DashboardFormGroup(props: IProps) {
    const { fieldset = false } = props;
    const Tag = fieldset ? "div" : props.tag ?? "li";
    const uniqueID = useUniqueID("formGroup-");
    const inputID = props.inputID ?? uniqueID;
    const labelID = inputID + "-label";

    return (
        <Tag
            className={cx(
                "form-group",
                { [`formGroup-${props.inputType}`]: !!props.inputType },
                { ["hasError"]: props.errors && !!props.errors.length },
                props.className,
            )}
            role={"group"}
            aria-labelledby={fieldset ? labelID : undefined}
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
