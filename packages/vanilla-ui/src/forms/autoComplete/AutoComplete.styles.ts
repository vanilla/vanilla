/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { InputSize } from "../../types";

export interface AutoCompleteClassesProps {
    size?: InputSize;
    isDisabled?: boolean;
    isClearable?: boolean;
    zIndex?: number;
}

export const autoCompleteClasses = ({
    size = "default",
    isDisabled = false,
    isClearable = false,
    zIndex = 1,
}: AutoCompleteClassesProps) => ({
    arrowButton: css({}),
    reachCombobox: css({
        width: "100%",
    }),
    inputContainer: css({
        position: "relative",
        display: "flex",
        width: "100%",
        alignItems: "baseline",
        justifyContent: "flex-start",
        border: "solid 1px #bfcbd8",
        borderRadius: 6,
        flexWrap: "wrap",
        ...{
            small: { paddingRight: !isDisabled && isClearable ? 40 : !isDisabled ? 16 : 0 },
            default: { paddingRight: !isDisabled && isClearable ? 44 : !isDisabled ? 20 : 0 },
        }[size],
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
    inputTokenTag: css({
        display: "flex",
        ...{
            small: { margin: "4px 0px 4px 8px", fontSize: "12px" },
            default: { margin: "5px 0px 5px 8px", fontSize: "12px" },
        }[size],
        backgroundColor: "#eeefef",
        borderRadius: 2,
        alignItems: "center",
        maxWidth: "85%",
        "& > label": {
            margin: 0,
            ...{
                small: { padding: "3px 6px", paddingRight: 0 },
                default: { padding: "4px 8px", paddingRight: 0 },
            }[size],
            fontWeight: "initial",
            "& + div": {
                paddingRight: 2,
            },
        },
    }),
    popover: css({
        "&[data-reach-combobox-popover]": {
            zIndex: zIndex + 1, // Always one above the current stack height
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
    checkmarkContainer: css({
        display: "flex",
        height: "1em",
    }),
    parentLabel: css({
        color: "#767676",
        fontSize: "12px",
    }),
    option: css({
        display: "flex",
        alignItems: "center",
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
                small: { width: 18, height: 18, right: -3, transform: "translateY(calc(0.5em - 50%))" },
                default: { width: 24, height: 24, right: -4, transform: "translateY(calc(0.5em - 50%))" },
            }[size],
        },
        "&:first-of-type&:last-of-type": {
            background: "rgba(3,125,188,0.08)",
        },
        "&[data-autocomplete-selected=true]": {
            background: "rgba(3,125,188,0.08)",
        },
    }),
    optionText: css({
        flex: 1,
    }),
    separator: css({
        listStyle: "none",
        height: "1px",
        backgroundColor: "rgb(221,222,224)",
        marginTop: "8px",
        marginBottom: "8px",
        border: "none",
        "& + &, &:last-child, &:first-child": {
            height: 0,
        },
    }),
    groupHeading: css({
        textAlign: "center",
        textTransform: "uppercase",
        fontWeight: 600,
        color: "#808080",
        ...{
            small: { padding: "6px 8px", fontSize: "13px" },
            default: { padding: "6px 8px", fontSize: "13px" },
        }[size],
        margin: 0,
    }),
    autoCompleteArrow: css({
        display: "flex",
    }),
    autoCompleteClear: css({
        display: "flex",
        pointerEvents: "auto",
        cursor: "pointer",
    }),
    autoCompleteClose: css({
        height: 8,
        width: 8,
        alignSelf: "center",
    }),
    input: css({
        cursor: "default",
        border: "none",
        flex: 1,
        minWidth: 100,
        "* + &": {
            height: 0,
            fontSize: 0,
        },
        "* + &:focus, * + &[data-state='interacting'], * + &[data-state='suggesting']": {
            height: "auto",
            fontSize: "inherit",
        },
    }),
});
