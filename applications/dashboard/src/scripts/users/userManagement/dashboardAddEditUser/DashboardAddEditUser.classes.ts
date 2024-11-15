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

    const form = css({
        height: "100%",
        minHeight: viewHeight(80),
        "& > section": {
            maxHeight: "none",
        },
    });

    const unifiedFormGroup = css({
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
