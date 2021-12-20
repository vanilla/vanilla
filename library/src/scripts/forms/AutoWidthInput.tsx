import { css, cx } from "@emotion/css";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import React, { useCallback, useEffect, useRef, useState } from "react";

export const AutoWidthInput = React.forwardRef(function AutoWidthInput(
    props: React.InputHTMLAttributes<HTMLInputElement>,
    ref: React.RefObject<HTMLInputElement>,
) {
    const spanRef = useRef<HTMLSpanElement>(null);
    const [minWidth, setMinWidth] = useState(0);

    const minWidthClass = css({
        label: "autoWidthSizer",
        width: 0,
        minWidth: minWidth,
        color: "#555a62",
    });

    const { value, placeholder } = props;
    const classes = autoWidthInputClasses();

    const measureSpan = useCallback(
        (content: string | undefined) => {
            if (!spanRef.current) {
                return;
            }

            content = content || placeholder || "";
            spanRef.current.innerText = content;
            // Measure the span widht.
            const rect = spanRef.current.getBoundingClientRect();
            let newWidth = rect.width + 12;
            newWidth = Math.min(Math.max(80, newWidth), 300);
            setMinWidth(newWidth);
        },
        [placeholder],
    );

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newValue = e.currentTarget.value;
        measureSpan(newValue);
        props.onChange?.(e);
    };

    useEffect(() => {
        measureSpan(value?.toString());
    }, [measureSpan, value]);

    return (
        <>
            <input
                {...props}
                ref={ref}
                className={cx(props.className, minWidthClass)}
                onChange={handleChange}
                disabled={props.disabled}
            />
            <span ref={spanRef} className={cx(classes.hiddenInputMeasure, props.className)}>
                {props.value}
            </span>
        </>
    );
});
