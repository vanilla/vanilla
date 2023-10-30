/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { inputClasses } from "@library/forms/inputStyles";
import { cx } from "@emotion/css";

interface IProps extends React.InputHTMLAttributes<HTMLInputElement> {
    value?: string | number;
}

export const TextInput = React.forwardRef(function TextInputImpl(props: IProps, ref: React.Ref<HTMLInputElement>) {
    const { className, value, ...rest } = props;

    let actualValue = value;

    if (actualValue === undefined || (props.type == "number" && (actualValue === null || Number.isNaN(actualValue)))) {
        actualValue = "";
    }

    return <input {...rest} ref={ref} className={cx(className, inputClasses().text)} value={actualValue} />;
});
