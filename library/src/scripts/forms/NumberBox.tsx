/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx, css } from "@emotion/css";
import React from "react";
import { InputSize } from "@vanilla/ui/src/types";
import { inputClasses } from "@library/forms/inputStyles";

export interface INumberBoxProps
    extends Omit<React.HTMLProps<HTMLInputElement>, "type" | "ref" | "size" | "value" | "min" | "max"> {
    size?: InputSize;
    container?: string;
    value: number | string;
    min?: number;
    max?: number;
    onChange?(value): void;
}

/**
 * Renders a standard `<input type="number"/>`.
 * This is a controlled input, use `onChange` to get the updated field value.
 * We can customize the size of the input with the `size` property.
 * The className property can be used to customize the text box but does not override the default style.
 */
export const NumberBox = React.forwardRef(function NumberBoxImpl(
    props: INumberBoxProps,
    forwardedRef: React.Ref<HTMLInputElement>,
) {
    const { size, value, onChange, min, max, onKeyDown, ...otherProps } = props;

    const handleIncrement = () => {
        const valueAsNumber = typeof value === "string" ? parseInt(value) : value;
        onChange && onChange(Math.min(valueAsNumber + 1, max ?? Number.MAX_SAFE_INTEGER));
    };

    const handleDecrement = () => {
        const valueAsNumber = typeof value === "string" ? parseInt(value) : value;
        onChange && onChange(Math.max(valueAsNumber - 1, min ?? 0));
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
                onChange={(e) => {
                    onChange && onChange(e.target.value);
                }}
                className={cx(inputClasses().inputText, classes[size ?? "default"])}
                onKeyDown={(e) => {
                    props.onKeyDown?.(e);
                }}
            />
            <div className={cx(classes.spinner, size === "small" ? classes.spinnerSmall : classes.spinnerDefault)}>
                <button
                    className={cx(classes.button, classes.buttonIncrease)}
                    disabled={props.disabled}
                    aria-hidden
                    tabIndex={-1}
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleIncrement();
                    }}
                >
                    +
                </button>
                <button
                    className={cx(classes.button, classes.buttonDecrease)}
                    disabled={props.disabled}
                    aria-hidden
                    tabIndex={-1}
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleDecrement();
                    }}
                >
                    &ndash;
                </button>
            </div>
        </div>
    );
});

const classes = {
    numberContainer: css({
        display: "inline-block",
        position: "relative",
    }),
    default: css({
        "&&&": {
            height: 36,
            lineHeight: "36px",
            padding: "0 12px",
            fontSize: "16px",
            minHeight: 36,
            marginTop: 0,
        },
    }),
    small: css({
        "&&&": {
            height: 28,
            lineHeight: "28px",
            padding: "0 8px",
            fontSize: "13px",
            minHeight: 28,
            marginTop: 0,
        },
    }),
    spinner: css({
        position: "absolute",
        top: 0,
        right: 0,
        display: "flex",
        flexDirection: "column",
        width: "50%",
        height: "100%",
    }),
    spinnerSmall: css({
        maxWidth: 20,
    }),
    spinnerDefault: css({
        maxWidth: 27,
    }),
    button: css({
        "&&": {
            cursor: "pointer",
            background: "none",
            border: 0,
            borderLeft: "solid 1px #bfcbd8",
            height: "50%",
            lineHeight: 1,
            fontSize: 11,
            minHeight: "auto",
            overflow: "hidden",

            "&:hover": {
                background: "#bfcbd82e",
            },
        },
    }),
    buttonIncrease: css({
        "&&": {
            borderBottom: "solid 1px #bfcbd8",
            borderRadius: "0 6 0 0",
        },
    }),
    buttonDecrease: css({
        "&&": {
            borderRadius: "0 0 6 0",
            marginTop: "-1px",
        },
    }),
};
