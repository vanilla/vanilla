/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";

const componentVars = {
    global: {
        font: {
            fontSize: 14,
            color: "#555a62",
            fontFamily: "inherit",
        },
    },
    selected: {
        background: "#e9f4fb",
        text: "#555a62",
    },
    disabled: {
        opacity: 0.4,
    },
};

export const rangePickerClasses = () => {
    const container = css({
        display: "inline-flex",
        justifyContent: "center",
        gap: 40,
        "@media(max-width: 500px)": {
            height: 430,
            gap: 0,
            flexDirection: "column",
        },
    });
    const picker = css({
        "@media(max-width: 500px)": {
            height: 215,
        },
        // Adjustments for month navigation
        "& .DayPicker-NavBar": {
            width: "100%",
            padding: "20px 0 0",
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            "& span": {
                ...componentVars.global.font,
                textAlign: "center",
                fontWeight: 600,
            },
            "& > button": {
                maxHeight: 19,
                padding: 0,
                "&[disabled]": {
                    opacity: componentVars.disabled.opacity,
                },
            },
        },
        "& .DayPicker-Month": {
            margin: "12px 0 0",
        },
        // Adjustments for weekday header
        "& .DayPicker-Weekday": {
            ...componentVars.global.font,
            fontWeight: 700,
            fontSize: 12,
            padding: "0 0 8px",
            width: 30,
        },
        "& .DayPicker-Day": {
            ...componentVars.global.font,
            borderRadius: 0,
            padding: 0,
            height: 24,
            width: 30,
            // Adjustments for selection
            "&--selected:not(.DayPicker-Day--start):not(.DayPicker-Day--end):not(.DayPicker-Day--outside)": {
                background: componentVars.selected.background,
                color: componentVars.selected.text,
            },
            // Dates in another month
            "&--outside": {
                opacity: 0.7,
            },
            // Future dates
            "&--disabled": {
                opacity: componentVars.disabled.opacity,
                pointerEvents: "none",
            },
            // Selection start and end caps
            "&--start:not(.DayPicker-Day--outside), &--end:not(.DayPicker-Day--outside)": {
                color: `${componentVars.selected.text}!important`,
                backgroundColor: `${componentVars.selected.background}!important`,
                fontWeight: 700,
            },
            "&--start": {
                borderRadius: "50% 0 0 50%",
            },
            "&--end": {
                borderRadius: "0 50% 50% 0",
            },
            "&--today": {
                fontWeight: "initial",
            },
        },
    });
    return { container, picker };
};
