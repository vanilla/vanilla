/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useToast } from "@library/features/toaster/ToastContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import SmartLink from "@library/routing/links/SmartLink";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n";
import React, { useEffect, useRef } from "react";

function ToastContent() {
    const root = css({
        display: "flex",
        flexDirection: "column",
        gap: 8,
        orphans: 2,
        "& a": {
            color: globalVariables().mainColors.primary.toString(),
        },
    });

    return (
        <div className={root}>
            <span>{t("Your email address is not confirmed.")}</span>
            <span>
                <SmartLink to={"/entry/emailconfirmrequest"}>{t("Resend confirmation email")}</SmartLink>
            </span>
        </div>
    );
}

/**
 * Checks if the current user's email is confirmed and
 * notify them if its unconfirmed
 */
export function useEmailConfirmationToast() {
    const { addToast } = useToast();
    const currentUser = useCurrentUser();
    const isGuest = currentUser?.userID === 0;

    useEffect(() => {
        if (currentUser && !isGuest) {
            if (!currentUser.emailConfirmed) {
                addToast({
                    body: <ToastContent />,
                    toastID: "email-confirmation",
                });
            }
        }
    }, [currentUser]);

    return null;
}
