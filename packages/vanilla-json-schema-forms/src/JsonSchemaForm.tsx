/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { forwardRef, useMemo } from "react";
import { JsonSchema, ISchemaRenderProps, IValidationResult } from "./types";
import { PartialSchemaForm } from "./PartialSchemaForm";

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
    const { instance, Form, FormSection, FormTabs, FormControl, FormControlGroup } = props;

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

    return (
        <PartialSchemaForm
            {...props}
            {...Memoized}
            path={[]}
            schema={schema}
            rootSchema={schema}
            rootInstance={instance}
        />
    );
});
