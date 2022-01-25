/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import React, { useMemo } from "react";
import { InputSize } from "../../types";
import { inputClasses } from "../shared/input.styles";

export interface INumberBoxProps
    extends Omit<React.HTMLProps<HTMLInputElement>, "type" | "ref" | "size" | "value" | "min" | "max"> {
    size?: InputSize;
    container?: string;
    value: number;
    min?: number;
    max?: number;
    onValueChange?(value): void;
}

/**
 * Renders a standard `<input type="number"/>`.
 * This is a controlled input, use `onValueChange` to get the updated field value.
 * We can customize the size of the input with the `size` property.
 * The className property can be used to customize the text box but does not override the default style.
 */
export const NumberBox = React.forwardRef(function NumberBoxImpl(
    props: INumberBoxProps,
    forwardedRef: React.Ref<HTMLInputElement>,
) {
    const { size, value, onValueChange, min, max, ...otherProps } = props;
    const classes = useMemo(() => inputClasses({ size }), [size]);

    const handleIncrement = () => {
        onValueChange && onValueChange(Math.min(value + 1, max ?? Number.MAX_SAFE_INTEGER));
    };

    const handleDecrement = () => {
        onValueChange && onValueChange(Math.max(value - 1, min ?? 0));
    };

    return (
        <div className={cx(classes.numberContainer, props.container)}>
            <input
                tabIndex={0}
                {...otherProps}
                ref={forwardedRef}
                type="number"
                role="spinbutton"
                value={value}
                onChange={(e) => onValueChange && onValueChange(e.target.value)}
                className={cx(classes.input, props.className)}
            />
            <div className={cx(classes.spinner)}>
                <button disabled={props.disabled} aria-hidden tabIndex={-1} onClick={() => handleIncrement()}>
                    +
                </button>
                <button disabled={props.disabled} aria-hidden tabIndex={-1} onClick={() => handleDecrement()}>
                    &ndash;
                </button>
            </div>
        </div>
    );
});
