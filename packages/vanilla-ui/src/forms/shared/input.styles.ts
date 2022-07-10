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
    numberContainer: css({
        display: "inline-block",
        position: "relative",
    }),
    spinner: css({
        position: "absolute",
        top: 0,
        right: 0,
        display: "flex",
        flexDirection: "column",
        width: "50%",
        height: "100%",

        ...{
            small: {
                maxWidth: 20,
            },
            default: {
                maxWidth: 27,
            },
        }[size],

        "& > button": {
            borderLeft: "solid 1px #bfcbd8",
            height: "50%",
            lineHeight: 1,
            fontSize: 11,
            "&:hover": {
                background: "#bfcbd82e",
            },
            "&:first-of-type": {
                borderBottom: "solid 1px #bfcbd8",
                borderRadius: "0 6 0 0",
            },
            "&:last-of-type": {
                borderRadius: "0 0 6 0",
                marginTop: "-2px",
            },
        },
    }),
});
