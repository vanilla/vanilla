import { css, cx } from "@emotion/css";
import { measureText } from "@vanilla/utils";
import React, { useState } from "react";

interface AutoWidthProps extends React.InputHTMLAttributes<HTMLInputElement> {
    maximumWidth?: number;
}

export const AutoWidthInput = React.forwardRef(function AutoWidthInput(
    props: AutoWidthProps,
    ref: React.RefObject<HTMLInputElement>,
) {
    const maxWidth = props.maximumWidth ?? 300;
    const width = measureText(String(props.value), 18);
    const minWidth = measureText(String(props.placeholder), 18);

    const [focused, setFocused] = useState(false);

    const minWidthClass = css({
        label: "autoWidthSizer",
        width: width,
        color: "#555a62",
        ...(props.placeholder && !props.value ? { minWidth: minWidth } : {}),
        ...(!focused ? { maxWidth: width > maxWidth ? maxWidth : width } : { maxWidth: maxWidth * 1.25 }),
    });

    const { maximumWidth, ...inputProps } = props;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        props.onChange?.(e);
    };

    return (
        <>
            <input
                {...inputProps}
                ref={ref}
                className={cx(props.className, minWidthClass)}
                onChange={handleChange}
                disabled={props.disabled}
                onFocus={() => setFocused(true)}
                onBlur={() => setFocused(false)}
            />
        </>
    );
});
