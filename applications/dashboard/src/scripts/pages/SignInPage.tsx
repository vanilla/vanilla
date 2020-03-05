/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useAuthActions } from "@dashboard/auth/AuthActions";
import { useAuthStoreState } from "@dashboard/auth/authReducer";
import { PasswordForm } from "@dashboard/auth/PasswordForm";
import SSOMethods from "@dashboard/auth/SSOMethods";
import Or from "@dashboard/forms/Or";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import DocumentTitle from "@library/routing/DocumentTitle";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import React, { useEffect } from "react";
import Message from "@library/messages/Message";

interface IProps {}

export default function SignInPage(props: IProps) {
    const pageTitleID = useUniqueID("signinPage-title");
    const { authenticators: authenticatorState } = useAuthStoreState();
    const { getAuthenticators } = useAuthActions();
    const { status } = authenticatorState;

    useEffect(() => {
        if (status === LoadStatus.PENDING) {
            getAuthenticators();
        }
    }, [status, getAuthenticators]);

    if (authenticatorState.status === LoadStatus.LOADING) {
        return <Loader />;
    }

    let showPassword = false;
    const ssoMethods =
        authenticatorState.data?.filter(a => {
            if (a.type === "password") {
                showPassword = true;
                return false;
            }
            return true;
        }) ?? [];

    return (
        <div className="authenticateUserCol">
            {authenticatorState.error && <Message isFixed stringContents={authenticatorState.error.message} />}
            <DocumentTitle title={t("Sign In")}>
                <h1 id={pageTitleID} className="isCentered">
                    {t("Sign In")}
                </h1>
            </DocumentTitle>
            <SSOMethods ssoMethods={ssoMethods} />
            <Or visible={showPassword && ssoMethods.length > 0} />
            {showPassword && <PasswordForm />}
        </div>
    );
}
