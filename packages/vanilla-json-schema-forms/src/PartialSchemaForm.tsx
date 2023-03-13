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
import { validateConditions, validationErrorsToFieldErrors } from "./utils";

export const RenderChildren = (props: React.PropsWithChildren<ISectionProps | IFormProps | IControlGroupProps>) => (
    <>{props.children}</>
);

interface IPartialProps extends IBaseSchemaFormProps, ISchemaRenderProps {
    isRequired?: boolean;
    inheritSchema?: JsonSchema;
    onChange(instance: any): void;
    onBlur?(): void;
    disabled?: boolean;
    groupName?: string;
    hideDescriptionInLabels?: boolean;
    size?: "small" | "default";
    autocompleteClassName?: string;
}

export function PartialSchemaForm(props: IPartialProps) {
    let {
        path,
        rootSchema,
        instance,
        rootInstance,
        onChange,
        onBlur,
        validation,
        FormControl,
        // Those default to a react component that simply renders children.
        Form = RenderChildren,
        FormSection = RenderChildren,
        FormControlGroup = RenderChildren,
        FormGroupWrapper,
        groupName,
        hideDescriptionInLabels,
        size,
        autocompleteClassName,
    } = props;

    const schema = props.inheritSchema
        ? ({
              ...props.inheritSchema,
              ...props.schema,
          } as JsonSchema)
        : props.schema;

    const form: IForm | undefined = schema["x-form"];
    let control: IFormControl | IFormControl[] | undefined = props.schema["x-control"];

    //exclude descriptions from form field labels
    if (control && hideDescriptionInLabels) {
        if (!Array.isArray(control) && control.description) {
            control = { ...control, description: "" };
        } else if (Array.isArray(control)) {
            control = control.map((controlEntry) => {
                return { ...controlEntry, description: "" };
            });
        }
    }

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
            <ConditionalWrap
                condition={!!FormGroupWrapper && sectionTitle}
                wrapper={(children: React.ReactChildren) =>
                    !!FormGroupWrapper && (
                        <FormGroupWrapper groupName={groupName} header={sectionTitle ?? ""}>
                            {children}
                        </FormGroupWrapper>
                    )
                }
            >
                <FormSection
                    errors={[]}
                    path={path}
                    pathString={`/${path.join("/")}`}
                    title={sectionTitle!}
                    instance={instance}
                    rootInstance={rootInstance}
                    schema={schema}
                    rootSchema={rootSchema}
                    validation={validation}
                >
                    {Object.entries(schema.properties).map(([key, value]: [string, JsonSchema]) => {
                        const pathString = `/${[...path, key].join("/")}`;
                        return (
                            <PartialSchemaForm
                                disabled={props.disabled || value?.disabled}
                                key={key}
                                path={[...path, key]}
                                errors={validationErrorsToFieldErrors(
                                    validation?.errors,
                                    value["x-control"]?.["errorPathString"] ?? pathString,
                                )}
                                pathString={pathString}
                                schema={value}
                                rootSchema={rootSchema}
                                instance={instance?.[key]}
                                rootInstance={rootInstance}
                                Form={Form}
                                FormSection={FormSection}
                                FormControl={FormControl}
                                FormControlGroup={FormControlGroup}
                                FormGroupWrapper={FormGroupWrapper}
                                onChange={(value) => {
                                    onChange({ ...instance, [key]: value });
                                }}
                                onBlur={onBlur}
                                isRequired={requiredProperties.includes(key)}
                                groupName={key}
                                validation={validation}
                                hideDescriptionInLabels={hideDescriptionInLabels}
                                size={size}
                                autocompleteClassName={autocompleteClassName}
                            />
                        );
                    })}
                </FormSection>
            </ConditionalWrap>
        );

        if (form) {
            const pathString = `/${path.join("/")}`;

            return (
                <FormWrapper
                    path={path}
                    pathString={pathString}
                    errors={validationErrorsToFieldErrors(
                        validation?.errors,
                        schema["x-control"]?.["errorPathString"] ?? pathString,
                    )}
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
            pathString={`/${path.join("/")}`}
            errors={props.errors}
            controls={visibleControls}
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
                    pathString={`/${path.join("/")}`}
                    errors={props.errors}
                    control={singleControl}
                    instance={instance}
                    rootInstance={rootInstance}
                    schema={schema}
                    rootSchema={rootSchema}
                    onChange={onChange}
                    required={props.isRequired}
                    validation={validation}
                    FormControl={FormControl}
                    onBlur={onBlur}
                    size={size}
                    autocompleteClassName={autocompleteClassName}
                />
            ))}
        </FormControlGroup>
    );
}

function ConditionalWrap({ condition, wrapper, children }) {
    return condition ? wrapper(children) : children;
}
