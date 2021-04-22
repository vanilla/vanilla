/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { InputSize } from "../../types";

export interface AutoCompleteClassesProps {
    size?: InputSize;
}

export const autoCompleteClasses = ({ size = "default" }: AutoCompleteClassesProps) => ({
    arrowButton: css({}),
    inputContainer: css({
        position: "relative",
    }),
    inputActions: css({
        display: "flex",
        flexDirection: "row-reverse",
        alignItems: "stretch",
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,

        ...{
            small: { padding: "0 8px" },
            default: { padding: "0 12px" },
        }[size],
    }),
    popover: css({
        "&[data-reach-combobox-popover]": {
            zIndex: 9999,
            background: "#fff",
            border: 0,
            borderRadius: 6,
            boxShadow: "0 5px 10px 0 rgba(0, 0, 0, 0.3)",
            maxHeight: "300px",
            overflow: "auto",
        },
    }),
    option: css({
        "&[data-reach-combobox-option]": {
            borderBottom: "1px solid #dddee0",

            ...{
                small: { padding: "6px 8px", fontSize: "13px" },
                default: { padding: "8px 12px", fontSize: "16px" },
            }[size],

            "&:last-child": {
                borderBottom: 0,
            },

            "&:hover": {
                background: "rgba(3,125,188,0.03)",
            },
        },
        "[data-suggested-value]": {
            fontWeight: "inherit",
        },
        "[data-user-value]": {
            fontWeight: 600,
        },
    }),
    autoCompleteArrow: css({
        display: "flex",
        pointerEvents: "none",
    }),
    autoCompleteClear: css({
        display: "flex",
        cursor: "pointer",
    }),
});
