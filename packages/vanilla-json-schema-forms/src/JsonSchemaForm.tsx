/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { forwardRef, useCallback, useEffect, useImperativeHandle, useMemo, useRef, useState } from "react";
import { JsonSchema, ISchemaRenderProps, IValidationResult, IFieldError } from "./types";
import { PartialSchemaForm, RenderChildren } from "./PartialSchemaForm";
import produce from "immer";
import { VanillaUIFormControl } from "./vanillaUIControl/VanillaUIFormControl";
import { VanillaUIFormControlGroup } from "./vanillaUIControl/VanillaUIFormControlGroup";
import { useFormValidation, ValidationProvider } from "./ValidationContext";
import { fieldErrorsToValidationErrors } from "./utils";

interface IProps extends ISchemaRenderProps {
    schema: JsonSchema | string;
    instance: any;
    /** false by default */
    autoValidate?: boolean;
    /** false by default */
    validateOnBlur?: boolean;
    onChange(instance: any): void;
    disabled?: boolean;
    vanillaUI?: boolean;
    onValidationStatusChange?(valid: boolean): void;
    hideDescriptionInLabels?: boolean;
    size?: "small" | "default";
    autocompleteClassName?: string;
    fieldErrors?: Record<string, IFieldError[]>;
}

export interface IJsonSchemaFormHandle {
    validate(): IValidationResult | undefined;
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
        validateOnBlur = false,
        instance,
        onChange,
        Form,
        FormSection,
        FormTabs,
        FormControl,
        FormControlGroup,
        FormGroupWrapper,
        vanillaUI = false,
        onValidationStatusChange,
        hideDescriptionInLabels = false,
        size = "default",
        autocompleteClassName,
    } = props;

    const formValidation = useFormValidation();

    const [_validation, setValidation] = useState<IValidationResult>();

    const validation = useMemo((): IValidationResult | undefined => {
        const isValid = (_validation?.isValid ?? true) && (props.fieldErrors ?? []).length === 0;
        return {
            isValid: isValid,
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
        let result: IValidationResult = {
            isValid: true,
        };
        const performValidation = (instance: any) => {
            result = formValidation.validate(schemaRef.current, instance);
        };
        // Validating might mutate the instance.
        const produced = produce(instanceRef.current, (draft) => {
            performValidation(draft);
        });

        if (produced !== instanceRef.current) {
            onChange(produced);
        }

        setValidation(result);
        return result;
    }, [formValidation]);

    useEffect(() => {
        if (!!validation && typeof validation?.isValid !== undefined) {
            onValidationStatusChange?.(validation.isValid);
        }
    }, [validation]);

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
            onBlur={validateOnBlur ? () => validate() : undefined}
            hideDescriptionInLabels={hideDescriptionInLabels}
            size={size}
            autocompleteClassName={autocompleteClassName}
        />
    );
});
