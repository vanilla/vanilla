/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { uuidv4 } from "@vanilla/utils";
import React, { useMemo, useState } from "react";
import * as Polymorphic from "../../polymorphic";
import { formGroupClasses } from "./FormGroup.styles";

export interface IFormGroupContext {
    sideBySide?: boolean;
    inputID: string;
    labelID: string;
    label?: string;
    setLabel(label: string): void;
    setLabelID(labelID: string): void;
}

export interface IFormGroupProps {
    sideBySide?: boolean;
    compact?: boolean;
    inputID?: string;
}

function noop() {}

/** @internal */
export const FormGroupContext = React.createContext<IFormGroupContext>({
    inputID: "",
    labelID: "",
    setLabel: noop,
    setLabelID: noop,
});

/**
 * A FormGroup renders a section of a form with a label and any input.
 * `FormGroupLabel` renders a label and `FormGroupInput` renders an input.
 * The only required property is for, which will be used to provide a valid id to the label and it's input.
 * It is possible to customize the component used to render this label with the `as` property.
 */
export const FormGroup = React.forwardRef(function FormGroupImpl(props, forwardedRef) {
    const { as: Comp = "div", inputID: propsInputID, children, sideBySide, compact, ...otherProps } = props;
    const inputID = useMemo(() => propsInputID || uuidv4(), [propsInputID]);
    const [label, setLabel] = useState<string | undefined>();
    const [labelID, setLabelID] = useState(`${inputID}_label`);
    const classes = useMemo(() => formGroupClasses({ sideBySide, compact }), [sideBySide, compact]);

    return (
        <Comp {...otherProps} className={cx(classes.formGroup, props.className)} ref={forwardedRef}>
            <FormGroupContext.Provider value={{ inputID, labelID, setLabelID, label, setLabel, sideBySide }}>
                {children}
            </FormGroupContext.Provider>
        </Comp>
    );
}) as Polymorphic.ForwardRefComponent<"div", IFormGroupProps>;
