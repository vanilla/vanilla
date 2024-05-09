/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import produce from "immer";
import get from "lodash/get";
import set from "lodash/set";
import { IControlProps, IFormProps, ISchemaRenderProps } from "./types";
import { findAllReferences, validateConditions } from "./utils";
import { stableObjectHash } from "@vanilla/utils";

/**
 * To be used internally by PartialSchemaForm.
 * This takes care of unreferencing pointers.
 */
export function FormWrapper(
    props: React.PropsWithChildren<Omit<IFormProps, "disabled"> & Pick<ISchemaRenderProps, "Form">>,
) {
    const { Form, children, ...formProps } = props;
    const { form, rootInstance, path } = formProps;
    // References are stable, they never change as the form stays the same.
    const references = React.useMemo(() => findAllReferences(form), [form]);
    // This array should change every time a value changes in rootInstance.
    // It's value will serve to stabilize the new form object.
    const unrefValues = React.useMemo(
        () =>
            references.map(({ ref }) => {
                try {
                    const value = get(rootInstance, ref);
                    if (value === undefined) {
                        return "";
                    }
                    return value;
                } catch {
                    return "";
                }
            }),
        [rootInstance, references],
    );
    // This form should only change value when the one of the unrefValues change.
    const stableUnwrappedForm = React.useMemo(
        () =>
            produce(form, (draft) => {
                // Replace ref string of each reference with it's unreferenced value.
                references.forEach(({ path, ref }, index) => {
                    const str: string = get(draft, path);
                    const value = unrefValues[index];
                    const newStr = str.replace(`{${ref.join(".")}}`, value);
                    set(draft, path, newStr);
                });
            }),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [stableObjectHash(unrefValues), references],
    );
    if (!Form) {
        return null;
    }
    return (
        <Form {...formProps} form={stableUnwrappedForm}>
            {children}
        </Form>
    );
}
