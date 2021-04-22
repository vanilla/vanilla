/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { labelize } from "@vanilla/utils";
import React, { HTMLProps, useContext, useEffect } from "react";
import * as Polymorphic from "../../polymorphic";
import { FormGroupContext } from "./FormGroup";
import { formGroupClasses } from "./FormGroup.styles";
import { FormGroupLabelText } from "./FormGroupLabelText";

export interface IFormGroupLabelProps {}

/**
 * Renders <label> with a labelized inputID and the proper aria attributes.
 * When no children are specified, <FormGroupLabelText /> is rendered.
 * It is possible to customize the component used to render this label with the `as` property.
 */
export const FormGroupLabel = React.forwardRef(function FormGroupLabelImpl(props, forwardedRef) {
    const { as: Comp = "label", children, ...otherProps } = props;
    const { inputID, labelID, setLabelID, sideBySide } = useContext(FormGroupContext);
    const classes = formGroupClasses({ sideBySide });
    const id = props.id ?? `${inputID}_label`;

    useEffect(() => {
        if (id !== labelID) {
            setLabelID(id);
        }
    }, [id, labelID, setLabelID]);

    return (
        <Comp
            htmlFor={inputID}
            id={id}
            {...otherProps}
            ref={forwardedRef}
            className={cx(classes.label, props.className)}
        >
            {children ?? <FormGroupLabelText />}
        </Comp>
    );
}) as Polymorphic.ForwardRefComponent<"label", IFormGroupLabelProps>;
