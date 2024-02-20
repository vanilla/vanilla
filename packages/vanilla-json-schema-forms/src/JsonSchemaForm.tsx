/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { forwardRef, useCallback, useEffect, useImperativeHandle, useMemo, useRef, useState } from "react";
import { ISchemaRenderProps, IFieldError, JSONSchemaType, JsonSchema } from "./types";
import { PartialSchemaForm, RenderChildren } from "./PartialSchemaForm";
import { VanillaUIFormControl } from "./vanillaUIControl/VanillaUIFormControl";
import { VanillaUIFormControlGroup } from "./vanillaUIControl/VanillaUIFormControlGroup";
import { useFormValidation, ValidationProvider } from "./ValidationContext";
import { fieldErrorsToValidationErrors, recursivelyCleanInstance } from "./utils";
import { ValidationResult } from "@cfworker/json-schema";

interface IProps extends ISchemaRenderProps {
    /** When possible, define `schema` as `JSONSchemaType<YourSchemaInterface>` and cast `as JsonSchema` when passing props to this component  */
    schema: JSONSchemaType | string;
    instance: any;
    /** false by default */
    autoValidate?: boolean;
    onChange(instance: any): void;
    onBlur?(fieldName: string): void;
    disabled?: boolean;
    vanillaUI?: boolean;
    hideDescriptionInLabels?: boolean;
    size?: "small" | "default";
    autocompleteClassName?: string;
    fieldErrors?: Record<string, IFieldError[]>;
}

export interface IJsonSchemaFormHandle {
    validate(): ValidationResult | undefined;
}

/**
 * Renders a form using a json schema.
 *
 * Note: Render props are memoized to prevent unnecessary re-rendering.
 * Please make sure you don't use any external dependencies that aren't passed as props to the component.
 */
export const JsonSchemaForm = forwardRef(function JsonSchemaFormWithContextImpl(
    props: IProps,
    ref: React.Ref<IJsonSchemaFormHandle>,
) {
    const ownRef = useRef<IJsonSchemaFormHandle>(null);

    useImperativeHandle(
        ref,
        () => ({
            validate: () => ownRef.current?.validate(),
        }),
        [ownRef],
    );

    return (
        <ValidationProvider>
            <JsonSchemaFormInstance {...props} ref={ownRef} />
        </ValidationProvider>
    );
});

const JsonSchemaFormInstance = forwardRef(function JsonSchemaFormImpl(
    props: IProps,
    ref: React.Ref<IJsonSchemaFormHandle>,
) {
    const {
        autoValidate = false,
        onBlur,
        instance,
        Form,
        FormSection,
        FormTabs,
        FormControl,
        FormControlGroup,
        FormGroupWrapper,
        vanillaUI = false,
        hideDescriptionInLabels = false,
        size = "default",
        autocompleteClassName,
    } = props;

    const formValidation = useFormValidation();

    const [_validation, setValidation] = useState<ValidationResult>();

    const validation = useMemo((): ValidationResult | undefined => {
        const isValid = (_validation?.valid ?? true) && (props.fieldErrors ?? []).length === 0;
        return {
            valid: isValid,
            errors: [...(_validation?.errors ?? []), ...fieldErrorsToValidationErrors(props.fieldErrors ?? {})],
        };
    }, [_validation, props.fieldErrors]);

    const schema = useMemo<JsonSchema>(
        () => (typeof props.schema === "string" ? JSON.parse(props.schema) : props.schema),
        [props.schema],
    );

    /** These are memoized to prevent re-rendering. */
    const Memoized = {
        // eslint-disable-next-line react-hooks/exhaustive-deps
        Form: useMemo(() => Form, []),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        FormSection: useMemo(() => FormSection, []),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        FormTabs: useMemo(() => FormTabs, []),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        FormControl: useMemo(() => FormControl ?? (vanillaUI ? VanillaUIFormControl : undefined), []),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        FormControlGroup: useMemo(() => FormControlGroup ?? (vanillaUI ? VanillaUIFormControlGroup : undefined), []),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        FormGroupWrapper: useMemo(() => FormGroupWrapper ?? (vanillaUI ? RenderChildren : undefined), []),
    };

    const schemaRef = useRef(schema);
    const instanceRef = useRef(instance);
    useEffect(() => {
        schemaRef.current = schema;
    }, [schema]);
    useEffect(() => {
        instanceRef.current = instance;
    }, [instance]);

    const validate = useCallback(() => {
        let result: ValidationResult = {
            valid: true,
            errors: [],
        };

        /**
         * undefined is not a value JSON Schema value, we should omit these from the instance
         * prior to validating to resolve type mismatch errors
         * required validation will continue to function as expected
         */
        const cleanInstance = recursivelyCleanInstance(instance, schemaRef.current);

        result = formValidation.validate(schemaRef.current, cleanInstance);

        setValidation(result);
        return result;
    }, [formValidation]);

    // Validate the form every time value changes.
    useEffect(() => {
        if (autoValidate !== false) {
            validate();
        }
    }, [instance, validate, autoValidate]);

    useImperativeHandle(
        ref,
        (): IJsonSchemaFormHandle => ({
            validate,
        }),
        [validate],
    );

    return (
        <PartialSchemaForm
            {...props}
            {...Memoized}
            disabled={props.disabled}
            pathString="/"
            errors={[]}
            path={[]}
            schema={schema}
            rootSchema={schema}
            rootInstance={instance}
            validation={validation}
            onBlur={onBlur}
            hideDescriptionInLabels={hideDescriptionInLabels}
            size={size}
            autocompleteClassName={autocompleteClassName}
        />
    );
});
