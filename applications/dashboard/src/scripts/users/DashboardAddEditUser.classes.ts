/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { media } from "@library/styles/styleShim";
import { viewHeight } from "csx";

export default function dashboardAddEditUserClasses(newPasswordFieldID?: string) {
    const message = css({
        "&&": { marginTop: 0 },
    });
    //feels a lot, but lots of custom grouping here, so need to adjust
    const modalForm = css({
        height: "100%",
        minHeight: viewHeight(80),

        ".input-wrap input": {
            lineHeight: "14px",
        },
        "& > section": {
            maxHeight: "none",
        },
        "& .formGroup-dropDown button": {
            paddingLeft: 4,
            paddingRight: 4,
        },
        "& .formGroup-radio .label-wrap": {
            alignSelf: "flex-start",
        },
        "& .formGroup-radio .input-wrap span": {
            fontWeight: 500,
        },
        "& .formGroup-radio .input-wrap label > span:first-of-type": {
            minWidth: 16,
        },
        "& .formGroup-checkBox .input-wrap label, .formGroup-radio .input-wrap label": {
            flexWrap: "nowrap",
        },
    });

    const unifiedFormGroup = css({
        "& .form-group:not(:last-of-type)": {
            borderBottom: "none",
        },
        "& .form-group:not(:first-of-type)": {
            paddingTop: 0,
            "& label > span:nth-of-type(2)": {
                fontWeight: 500,
            },
        },
        "& .formGroup-checkBox:not(:first-of-type)": {
            paddingTop: 0,
        },
        "& .formGroup-checkBox:not(:last-of-type)": {
            paddingBottom: 0,
        },

        //align with above inputs since no label for these
        ...(newPasswordFieldID && {
            [`& #${newPasswordFieldID}-label`]: {
                flex: "0 0 44.6666666667%",
                ...media(
                    {
                        maxWidth: 543,
                    },
                    { flex: "0 0 100%" },
                ),
            },
            [`& #${newPasswordFieldID}-label + .input-wrap`]: {
                flex: "0 0 55.3333333333%",
                ...media(
                    {
                        maxWidth: 543,
                    },
                    { flex: "0 0 92%", marginLeft: 30 },
                ),
            },
        }),
        "& .formGroup-custom button": {
            marginLeft: 30,
        },
    });
    const unifiedFormGroupWrapper = css({
        display: "flex",
        "& > .label-wrap": {
            alignSelf: "flex-start",
            fontWeight: 600,
        },
        "& > .input-wrap": {
            "& .input-wrap,  & .label-wrap": {
                flex: "none",
            },
        },
        "& .form-group:first-of-type": {
            "& label > span:nth-of-type(2)": {
                fontWeight: 500,
            },
        },
    });

    return {
        unifiedFormGroup,
        unifiedFormGroupWrapper,
        modalForm,
        message,
    };
}
