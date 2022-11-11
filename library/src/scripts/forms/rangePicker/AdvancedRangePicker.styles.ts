/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */
import { css } from "@emotion/css";

const localVars = {
    width: 250,
    spacing: 26,
    global: {
        font: {
            fontSize: 14,
            color: "#555a62",
            fontFamily: "inherit",
        },
    },
};

export const advancedRangePickerClasses = () => {
    const layout = css({
        width: "100%",
        maxWidth: localVars.width * 2,
        display: "flex",
        gap: localVars.spacing,
        padding: "20px 0 16px",
    });
    const form = css({
        width: localVars.width,
        "& h4": {
            ...localVars.global.font,
            fontWeight: 600,
            marginBottom: "15px",
        },
    });
    const invalid = css({
        padding: "0 0 20px",
        width: "100%",
        display: "block",
        color: "#d0021b",
        fontSize: 12,
        marginTop: -4,
    });
    return {
        layout,
        form,
        invalid,
    };
};
