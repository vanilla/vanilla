/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
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
    let { label, legend, description, fullSize, inputType, tooltip, labelType } = controls[0];
    const isFieldset = ["radio"].includes(inputType);
    if (fullSize || inputType === "upload") {
        return <>{children}</>;
    }

    labelType =
        controls[0].labelType ?? (["toggle"].includes(controls[0].inputType) ? DashboardLabelType.WIDE : labelType);

    inputType =
        controls[0].inputType === "textBox" && controls[0].type === "textarea" ? ("textarea" as any) : inputType;

    return (
        <DashboardFormGroup
            label={legend ?? label ?? ""}
            description={description}
            inputType={inputType}
            tooltip={tooltip}
            labelType={props.labelType ?? (labelType as DashboardLabelType)}
            inputID={controls[0].inputID}
            fieldset={isFieldset}
            required={required}
            errors={errors}
            checkPosition={"checkPosition" in controls[0] ? controls[0].checkPosition : undefined}
            className={props.controls[0].labelClassname}
            noBorder={controls[0].noBorder}
            isNested={controls[0].isNested}
        >
            {children}
        </DashboardFormGroup>
    );
}
