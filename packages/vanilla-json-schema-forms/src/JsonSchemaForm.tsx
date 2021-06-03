/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { forwardRef, useCallback, useEffect, useImperativeHandle, useMemo, useRef, useState } from "react";
import { JsonSchema, ISchemaRenderProps, IValidationResult } from "./types";
import { PartialSchemaForm } from "./PartialSchemaForm";
import Ajv from "ajv";
import produce from "immer";

interface IProps extends ISchemaRenderProps {
    schema: JsonSchema | string;
    instance: any;
    /** true by default */
    autoValidate?: boolean;
    onChange(instance: any): void;
}

export interface IJsonSchemaFormHandle {
    validate(): IValidationResult;
}

/**
 * Renders a form using a json schema.
 *
 * Note: Render props are memoized to prevent unnecessary re-rendering.
 * Please make sure you don't use any external dependencies that aren't passed as props to the component.
 */
export const JsonSchemaForm = forwardRef(function JsonSchemaFormImpl(
    props: IProps,
    ref: React.Ref<IJsonSchemaFormHandle>,
) {
    const { autoValidate, instance, onChange, Form, FormSection, FormTabs, FormControl, FormControlGroup } = props;
    const [validation, setValidation] = useState<IValidationResult>();

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
        FormControl: useMemo(() => FormControl, []),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        FormControlGroup: useMemo(() => FormControlGroup, []),
    };

    const schemaRef = useRef(schema);
    const instanceRef = useRef(instance);
    useEffect(() => {
        schemaRef.current = schema;
    }, [schema]);
    useEffect(() => {
        instanceRef.current = instance;
    }, [instance]);

    const ajv = useMemo(() => {
        const ajv = new Ajv({
            // Will remove additional properties if a schema has { additionalProperties: false }.
            removeAdditional: true,
            // Will set defaults automatically.
            useDefaults: true,
            // Will make sure types match the schema.
            coerceTypes: true,
            // Lets us use discriminators to validate oneOf schemas properly (and remove additional properties)
            discriminator: true,
        });
        // Add x-control as a suppported keyword of the schema.
        ajv.addKeyword("x-control");
        // Add x-form as a suppported keyword of the schema.
        ajv.addKeyword("x-form");
        return ajv;
    }, []);

    const validate = useCallback(() => {
        const performValidation = (instance: any) => {
            ajv.validate(schemaRef.current, instance);
        };
        // Validating might mutate the instance.
        const produced = produce(instanceRef.current, (draft) => {
            performValidation(draft);
        });

        if (produced !== instanceRef.current) {
            onChange(produced);
        }
        // Create a validation result.
        const result: IValidationResult = {
            isValid: !ajv.errors || !ajv.errors.length,
            errors: ajv.errors || [],
        };
        setValidation(result);
        return result;
    }, [ajv, onChange]);

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
            path={[]}
            schema={schema}
            rootSchema={schema}
            rootInstance={instance}
            validation={validation}
        />
    );
});
