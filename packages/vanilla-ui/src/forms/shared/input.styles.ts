/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { InputSize } from "../../types";

export interface InputClassesProps {
    size?: InputSize;
    useInputRowSize?: boolean;
}

/**
 * These classes are used to render a standard input container.
 * In other words, a rounded, bordered box which change colors when focused.
 * Pass a size property to customize the size of the box.
 * @param size
 * @returns
 * @internal
 */
export const inputClasses = ({ size = "default" }: InputClassesProps) => ({
    input: css({
        borderRadius: 6,
        color: "#3c4146",
        border: "solid 1px #bfcbd8",
        backgroundColor: "#ffffff",
        fontWeight: "initial",
        width: "100%",

        ...{
            small: {
                height: 28,
                lineHeight: "28px",
                padding: "0 8px",
                fontSize: "13px",
            },
            default: {
                height: 36,
                lineHeight: "36px",
                padding: "0 12px",
                fontSize: "16px",
            },
        }[size],

        "&:focus": {
            borderColor: "#037dbc",
            outline: "none",
        },
    }),
});
