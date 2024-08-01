/**
 * @copyright 2009-2023 Vanilla Forums Inc.
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
    const form = css({
        height: "100%",
        minHeight: viewHeight(80),

        ".input-wrap input": {
            lineHeight: "14px",
        },
        "& > section": {
            maxHeight: "none",
        },
        "& .formGroup-dropDown button, & .formGroup-tokens button": {
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
        "& .form-group:not(:last-child)": {
            borderBottom: "none",
        },
        "& .form-group:not(:first-child)": {
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

        //avoid empty vertical space on mobile, since there is no label rendered for this input
        ...(newPasswordFieldID && {
            [`#${newPasswordFieldID}-label`]: {
                ...media(
                    {
                        maxWidth: 543,
                    },
                    { display: "none" },
                ),
            },
        }),
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

        // Fix some checkbox alignment.
        "& .label-wrap:empty": {
            display: "none",
        },
    });

    const topLevelError = css({
        marginTop: 16,
    });
    const buttonContainer = css({
        width: "100%",
        display: "flex",
        justifyContent: "flex-end",
    });
    const button = css({
        marginTop: 16,
        marginBottom: -16,
    });

    const headerActions = css({
        display: "flex",
        gap: 8,
        alignItems: "center",
    });

    return {
        unifiedFormGroup,
        unifiedFormGroupWrapper,
        form,
        message,
        topLevelError,
        buttonContainer,
        button,
        headerActions,
    };
}
