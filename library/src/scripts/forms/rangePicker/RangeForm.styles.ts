/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */
import { css } from "@emotion/css";

export const rangeFormClasses = () => {
    const datePicker = css({
        paddingTop: 8,
        "& .DayPicker": {
            "&-NavBar": {
                padding: 0,
            },
        },
    });
    const input = css({
        width: 144,
        '&[type="number"]': {
            width: 80,
        },
    });
    const textPopover = css({
        position: "absolute",
        width: 144,
    });
    return {
        datePicker,
        input,
        textPopover,
    };
};
