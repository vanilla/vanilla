/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { WidgetFormControl } from "@dashboard/widgets/WidgetFormControl";
import get from "lodash/get";
import { IJsonSchema } from "@dashboard/widgets/JsonSchemaTypes";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";
import { notEmpty } from "@vanilla/utils";

interface IPartialProps {
    path: string;
    schema: IJsonSchema;

    // The value of the subset of the form.
    instance: any;
    isRequired?: boolean;

    // The root value of the form.
    rootInstance: any;
    onChange(instance: any): void;
}

function WidgetPartialSchemaForm(props: IPartialProps) {
    const { schema, path, instance, rootInstance, onChange } = props;
    if (schema.type === "object") {
        const requiredProperties = schema.required ?? [];
        return (
            <>
                {Object.entries(schema.properties).map(([key, value]) => (
                    <WidgetPartialSchemaForm
                        key={key}
                        path={`${path}.${key}`}
                        schema={value}
                        instance={instance[key]}
                        rootInstance={rootInstance}
                        onChange={(value) => {
                            onChange({ ...instance, [key]: value });
                        }}
                        isRequired={requiredProperties.includes(key)}
                    />
                ))}
            </>
        );
    }
    const control = schema["x-control"];
    const controls = Array.isArray(control) ? control : [control];
    return (
        <>
            {controls.filter(notEmpty).map((singleControl, index) => {
                const { label, description, conditions } = singleControl;
                if (conditions) {
                    const evaluated: boolean[] = conditions.map(({ fieldName, values }) =>
                        values.includes(get(rootInstance, fieldName, schema.default)),
                    );
                    if (evaluated.some((ev) => !ev)) return null;
                }
                return (
                    <DashboardFormGroup key={index} label={label ?? t("(Untitled)")} description={description}>
                        <WidgetFormControl
                            formControl={singleControl}
                            value={instance}
                            schema={schema}
                            onChange={onChange}
                            isRequired={props.isRequired}
                        />
                    </DashboardFormGroup>
                );
            })}
        </>
    );
}

interface IProps {
    schema: IJsonSchema;
    // The full value of the form.
    instance: any;
    onChange(instance: any): void;
}

export function WidgetFormGenerator(props: IProps) {
    if (Object.entries(props.schema).length === 0) {
        return <div>{t("There are no configuration options for this widget.")}</div>;
    }

    return <WidgetPartialSchemaForm path="instance" rootInstance={props.instance} {...props} />;
}
