/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { labelize } from "@vanilla/utils";
import React, { useContext } from "react";
import * as Polymorphic from "../../polymorphic";
import { FormGroupContext } from "./FormGroup";

type RenderProp = (props: { id: string; placeholder?: string; "aria-labelledby": string }) => React.ReactNode;

export interface IFormGroupInputProps {
    as?: keyof JSX.IntrinsicElements;
    children?: React.ReactNode | RenderProp;
}

/**
 * Passes the proper aria attributes to any input.
 * It's children property is a render prop. For example: {(props) => <input {...props} />}
 * It is possible to customize the component used to render this label with the `as` property.
 */
export const FormGroupInput = React.forwardRef(function FormGroupInputImpl(props, forwardedRef) {
    const { as: Comp = "div", children, ...otherProps } = props;
    const { inputID, labelID, label } = useContext(FormGroupContext);
    return (
        <Comp {...otherProps} ref={forwardedRef}>
            {typeof children === "function"
                ? (children as RenderProp)({ id: inputID, placeholder: label, "aria-labelledby": labelID })
                : children}
        </Comp>
    );
}) as Polymorphic.ForwardRefComponent<"div", IFormGroupInputProps>;
