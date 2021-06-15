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
        pointerEvents: "none",
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
        "&[data-autocomplete-state=selected] [data-user-value]": {
            fontWeight: "inherit",
        },
    }),
    option: css({
        display: "flex",

        "&[data-reach-combobox-option]": {
            ...{
                small: { padding: "6px 8px", fontSize: "13px" },
                default: { padding: "8px 12px", fontSize: "16px" },
            }[size],

            "&:hover, &[data-highlighted]": {
                background: "rgba(3,125,188,0.08)",
            },
        },
        "[data-suggested-value]": {
            fontWeight: "inherit",
        },
        "[data-user-value]": {
            fontWeight: 600,
        },
        svg: {
            position: "relative",

            ...{
                small: { width: 18, right: -3 },
                default: { width: 24, right: -4 },
            }[size],
        },
    }),
    optionText: css({
        flex: 1,
    }),
    autoCompleteArrow: css({
        display: "flex",
    }),
    autoCompleteClear: css({
        display: "flex",
        pointerEvents: "auto",
        cursor: "pointer",
    }),
    input: css({
        cursor: "default",
    }),
});
