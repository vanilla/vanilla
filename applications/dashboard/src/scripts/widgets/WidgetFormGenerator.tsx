/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { t } from "@library/utility/appUtils";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";

interface IProps {
    schema: JsonSchema;
    // The full value of the form.
    instance: any;
    onChange(instance: any): void;
}

export function WidgetFormGenerator(props: IProps) {
    if (Object.entries(props.schema).length === 0) {
        return <div>{t("There are no configuration options for this widget.")}</div>;
    }
    return (
        <JsonSchemaForm
            {...props}
            autoValidate
            FormSection={({ title, children }) => (
                <>
                    {title && <DashboardFormSubheading>{title}</DashboardFormSubheading>}
                    {children}
                </>
            )}
            FormControl={DashboardFormControl}
            FormControlGroup={(props) => <DashboardFormControlGroup {...props} sideBySide />}
        />
    );
}
