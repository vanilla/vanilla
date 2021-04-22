/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { labelize } from "@vanilla/utils";
import React, { useContext } from "react";
import * as Polymorphic from "../../polymorphic";
import { FormGroupContext } from "./FormGroup";

export interface IFormGroupLabelTextProps {}

/**
 * Renders the labelized inputID.
 * For example: inputID="firstName" becomes "First Name".
 */
export const FormGroupLabelText = React.forwardRef(function FormGroupLabelTextImpl(props, forwardedRef) {
    const { as: Comp = "span", children, ...otherProps } = props;
    const { inputID } = useContext(FormGroupContext);
    const label = labelize(inputID);

    return (
        <Comp {...otherProps} ref={forwardedRef}>
            {label}
        </Comp>
    );
}) as Polymorphic.ForwardRefComponent<"span", IFormGroupLabelTextProps>;
