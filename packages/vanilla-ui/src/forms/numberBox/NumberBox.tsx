/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import React from "react";
import { InputSize } from "../../types";
import { inputClasses } from "../shared/input.styles";

export interface INumberBoxProps extends Omit<React.HTMLProps<HTMLInputElement>, "type" | "ref" | "size"> {
    size?: InputSize;
    container?: string;
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
    const { size, value, onValueChange, ...otherProps } = props;
    const classes = inputClasses({ size });
    const [controlledValue, setControlledValue] = React.useState(value || 0);

    React.useEffect(() => {
        setControlledValue(value || 0);
    }, [value]);

    React.useEffect(() => {
        onValueChange && onValueChange(controlledValue);
    }, [controlledValue]);

    const handleIncrement = () => {
        setControlledValue((prevState) => {
            const { max } = props;
            const val = typeof prevState === "number" ? prevState : 0;
            if (max) {
                if (val + 1 <= max) {
                    return val + 1;
                }
                return val;
            } else {
                return val + 1;
            }
        });
    };

    const handleDecrement = () => {
        setControlledValue((prevState) => {
            const { min } = props;
            const val = typeof prevState === "number" ? prevState : 0;
            if (min !== undefined) {
                if (val - 1 >= min) {
                    return val - 1;
                }
                return val;
            } else {
                return val - 1;
            }
        });
    };

    return (
        <div className={cx(classes.numberContainer, props.container)}>
            <input
                tabIndex={0}
                {...otherProps}
                ref={forwardedRef}
                type="number"
                role="spinbutton"
                value={controlledValue}
                onChange={(e) => setControlledValue(e.target.value)}
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
