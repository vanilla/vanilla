/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import produce from "immer";
import get from "lodash/get";
import set from "lodash/set";
import { IControlProps, ISchemaRenderProps } from "./types";
import { findAllReferences, validateConditions } from "./utils";
import { stableObjectHash } from "@vanilla/utils";

/**
 * To be used internally by PartialSchemaForm.
 * This takes care of unreferencing pointers and evaluating conditions.
 */
export function FormControlWrapper(props: IControlProps & Pick<ISchemaRenderProps, "FormControl">) {
    const { FormControl, ...controlProps } = props;
    const { control, rootInstance, path } = controlProps;
    const { conditions } = control;
    // References are stable, they never change as the control stays the same.
    const references = React.useMemo(() => findAllReferences(control), [control]);
    // This array should change every time a value changes in rootInstance.
    // It's value will serve to stabilize the new control object.
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
    // This control should only change value when the one of the unrefValues change.
    const stableUnwrappedControl = React.useMemo(
        () =>
            produce(control, (draft) => {
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
    const conditionsValidation = validateConditions(conditions ?? [], rootInstance);
    const disabled = conditionsValidation.conditions.some((c) => c.disable) || props.disabled;
    if (!FormControl) {
        return <></>;
    }
    return (
        <FormControl
            {...controlProps}
            disabled={disabled}
            control={stableUnwrappedControl}
            size={props.size}
            autocompleteClassName={props.autocompleteClassName}
        />
    );
}
