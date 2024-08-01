/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import React, { useContext, useMemo } from "react";
import { FormGroupContext } from "./FormGroup";
import { formGroupClasses } from "./FormGroup.styles";

type RenderProp = (props: { id: string; placeholder?: string; "aria-labelledby": string }) => React.ReactNode;

export interface IFormGroupInputProps {
    className?: string;
    children?: React.ReactNode | RenderProp;
}

/**
 * Passes the proper aria attributes to any input.
 * It's children property is a render prop. For example: {(props) => <input {...props} />}
 * It is possible to customize the component used to render this label with the `as` property.
 */
export const FormGroupInput = React.forwardRef(function FormGroupInputImpl(
    props: IFormGroupInputProps,
    forwardedRef: React.Ref<HTMLDivElement>,
) {
    const { children, className, ...otherProps } = props;
    const { inputID, labelID, sideBySide } = useContext(FormGroupContext);
    const classes = useMemo(() => formGroupClasses({ sideBySide }), [sideBySide]);

    return (
        <div {...otherProps} className={cx(classes.inputContainer, className)} ref={forwardedRef}>
            {typeof children === "function"
                ? (children as RenderProp)({ id: inputID, "aria-labelledby": labelID })
                : children}
        </div>
    );
});
