/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import React, { useMemo } from "react";
import { InputSize } from "../../types";
import { inputClasses } from "../shared/input.styles";

export interface ITextBoxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, "type" | "ref" | "size"> {
    size?: InputSize;
}

/**
 * Renders a standard `<input type="text"/>`.
 * We can customize the size of the input with the `size` property.
 * The className property can be used to customize the text box but does not override the default style.
 */
export const TextBox = React.forwardRef(function TextBoxImpl(
    props: ITextBoxProps,
    forwardedRef: React.Ref<HTMLInputElement>,
) {
    const { size, value, ...otherProps } = props;
    const classes = useMemo(() => inputClasses({ size }), [size]);
    return (
        <input
            tabIndex={0}
            {...otherProps}
            value={value ?? ""}
            ref={forwardedRef}
            type="text"
            className={cx(classes.input, props.className)}
        />
    );
});
