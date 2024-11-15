/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControlGroup";
import { DashboardFormStyleContext, IDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";
import { JsonSchemaForm, type IJsonSchemaFormHandle } from "@vanilla/json-schema-forms";

type IProps = Omit<React.ComponentProps<typeof JsonSchemaForm>, "FormControl" | "FormControlGroup"> &
    IDashboardFormStyle;

export const DashboardSchemaForm = React.forwardRef(function DashboardSchemaForm(
    props: IProps,
    ref?: React.Ref<IJsonSchemaFormHandle>,
) {
    return (
        <DashboardFormStyleContext.Provider
            value={{
                compact: props.compact,
                forceVerticalLabels: props.forceVerticalLabels,
            }}
        >
            <JsonSchemaForm
                FormControl={DashboardFormControl}
                FormControlGroup={DashboardFormControlGroup}
                {...props}
                ref={ref}
            />
        </DashboardFormStyleContext.Provider>
    );
});
