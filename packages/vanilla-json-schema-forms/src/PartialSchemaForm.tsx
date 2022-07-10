/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import {
    IBaseSchemaFormProps,
    IControlGroupProps,
    IForm,
    IFormControl,
    IFormProps,
    ISchemaRenderProps,
    ISectionProps,
    JsonSchema,
} from "./types";
import { notEmpty } from "@vanilla/utils";
import { TabbedSchemaForm } from "./TabbedSchemaForm";
import { FormControlWrapper } from "./FormControlWrapper";
import { FormWrapper } from "./FormWrapper";
import { validateConditions } from "./utils";

const RenderChildren = (props: React.PropsWithChildren<ISectionProps | IFormProps | IControlGroupProps>) => (
    <>{props.children}</>
);

interface IPartialProps extends IBaseSchemaFormProps, ISchemaRenderProps {
    isRequired?: boolean;
    inheritSchema?: JsonSchema;
    onChange(instance: any): void;
    disabled?: boolean;
}

export function PartialSchemaForm(props: IPartialProps) {
    let {
        path,
        rootSchema,
        instance,
        rootInstance,
        onChange,
        validation,
        FormControl,
        // Those default to a react component that simply renders children.
        Form = RenderChildren,
        FormSection = RenderChildren,
        FormControlGroup = RenderChildren,
    } = props;

    const schema = props.inheritSchema
        ? ({
              ...props.inheritSchema,
              ...props.schema,
          } as JsonSchema)
        : props.schema;

    const form: IForm | undefined = schema["x-form"];
    const control: IFormControl | IFormControl[] | undefined = props.schema["x-control"];
    const controls = control && (Array.isArray(control) ? control : [control]);

    // Render a tabbed form.
    if (schema.type === "object" && !Array.isArray(control) && control?.inputType === "tabs") {
        return <TabbedSchemaForm {...props} />;
    }

    // Recursively render a subset of a schema.
    if (schema.type === "object" && (!controls || !controls[0]?.inputType)) {
        const requiredProperties = schema.required ?? [];
        let sectionTitle: string | null = null;
        if (!Array.isArray(control) && control?.label) {
            sectionTitle = control?.label;
        }
        const section = (
            <FormSection
                path={path}
                title={sectionTitle!}
                instance={instance}
                rootInstance={rootInstance}
                schema={schema}
                rootSchema={rootSchema}
                validation={validation}
            >
                {Object.entries(schema.properties).map(([key, value]: [string, JsonSchema]) => {
                    return (
                        <PartialSchemaForm
                            disabled={props.disabled || value?.disabled}
                            key={key}
                            path={[...path, key]}
                            schema={value}
                            rootSchema={rootSchema}
                            instance={instance?.[key]}
                            rootInstance={rootInstance}
                            Form={Form}
                            FormSection={FormSection}
                            FormControl={FormControl}
                            FormControlGroup={FormControlGroup}
                            onChange={(value) => {
                                onChange({ ...instance, [key]: value });
                            }}
                            isRequired={requiredProperties.includes(key)}
                        />
                    );
                })}
            </FormSection>
        );
        if (form) {
            return (
                <FormWrapper
                    path={path}
                    form={form}
                    Form={Form}
                    instance={instance}
                    rootInstance={rootInstance}
                    schema={schema}
                    rootSchema={rootSchema}
                    validation={validation}
                >
                    {section}
                </FormWrapper>
            );
        }
        return section;
    }

    // No controls were defined. Nothing else to do.
    const validControls = controls && controls.filter(notEmpty);
    if (!validControls || !validControls.length) {
        return null;
    }

    // Check conditions for controls
    const visibleControls = validControls.filter(({ conditions }) => {
        const conditionsValidation = validateConditions(conditions ?? [], rootInstance);
        const disabled = conditionsValidation.conditions.some((c) => c.disable);
        return disabled || conditionsValidation.isValid;
    });
    if (!visibleControls.length) {
        return null;
    }

    // Render a control group and the controls within.
    return (
        <FormControlGroup
            path={path}
            controls={validControls}
            instance={instance}
            rootInstance={rootInstance}
            schema={schema}
            rootSchema={rootSchema}
            validation={validation}
        >
            {visibleControls.map((singleControl, index) => (
                <FormControlWrapper
                    disabled={props.disabled}
                    key={`${path.join("/")}[${index}]`}
                    path={path}
                    control={singleControl}
                    instance={instance}
                    rootInstance={rootInstance}
                    schema={schema}
                    rootSchema={rootSchema}
                    onChange={onChange}
                    required={props.isRequired}
                    validation={validation}
                    FormControl={FormControl}
                />
            ))}
        </FormControlGroup>
    );
}
