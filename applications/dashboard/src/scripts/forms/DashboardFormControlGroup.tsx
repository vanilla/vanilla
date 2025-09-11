/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import { IControlGroupProps } from "@vanilla/json-schema-forms";
import { IFormGroupProps } from "@vanilla/ui";
import React from "react";

/**
 * This is intended for use in the JsonSchemaForm component
 * @param props
 * @returns
 */

export function DashboardFormControlGroup(
    props: React.PropsWithChildren<IControlGroupProps> & IFormGroupProps & { labelType?: DashboardLabelType },
) {
    const { children, controls, required, errors } = props;
    const control = controls[0];
    let { label, legend, description, fullSize, inputType, tooltip, labelType } = control;

    let isFieldset = ["radio"].includes(inputType);
    if (control.inputType === "custom" && !!legend) {
        isFieldset = true;
    }

    if (fullSize || inputType === "upload") {
        return <>{children}</>;
    }

    labelType = control.labelType ?? (["toggle"].includes(control.inputType) ? DashboardLabelType.WIDE : labelType);

    inputType = control.inputType === "textBox" && control.type === "textarea" ? ("textarea" as any) : inputType;

    return (
        <DashboardFormGroup
            label={legend ?? label ?? ""}
            description={description}
            inputType={inputType}
            tooltip={tooltip}
            labelType={props.labelType ?? (labelType as DashboardLabelType)}
            inputID={control.inputID}
            fieldset={isFieldset}
            required={required}
            errors={errors}
            checkPosition={"checkPosition" in control ? control.checkPosition : undefined}
            className={control.labelClassname}
            noBorder={control.noBorder}
            isNested={control.isNested}
        >
            {children}
        </DashboardFormGroup>
    );
}
