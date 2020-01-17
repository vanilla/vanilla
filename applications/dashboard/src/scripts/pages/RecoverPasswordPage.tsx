/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useAuthActions } from "@dashboard/auth/AuthActions";
import { useAuthStoreState } from "@dashboard/auth/authReducer";
import RememberPasswordLink from "@dashboard/auth/RememberPasswordLink";
import { LoadStatus } from "@library/@types/api/core";
import { getFieldErrors, getGlobalErrorMessage } from "@library/apiv2";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import InputTextBlock from "@library/forms/InputTextBlock";
import Paragraph from "@library/layout/Paragraph";
import DocumentTitle from "@library/routing/DocumentTitle";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import React, { useEffect, useRef, useState } from "react";

interface IProps {}

export default function RecoverPasswordPage(props: IProps) {
    const pageTitleID = useUniqueID("recoverPassword-title");
    const [email, setEmail] = useState("");
    const emailRef = useRef<InputTextBlock | null>(null);
    const pageTitle = (
        <DocumentTitle title={t("Recover Password")}>
            <h1 id={pageTitleID} className="isCentered">
                {t("Recover Password")}
            </h1>
        </DocumentTitle>
    );
    const { resetPassword } = useAuthActions();
    const { status, error } = useAuthStoreState().passwordReset;
    const allowEdit = status !== LoadStatus.LOADING;

    useEffect(() => {
        if (status === LoadStatus.ERROR) {
            emailRef.current?.focus();
        }
    }, [status]);

    if (status === LoadStatus.SUCCESS) {
        return (
            <div className="authenticateUserCol">
                {pageTitle}
                <Paragraph className="authenticateUser-paragraph">
                    {t("A message has been sent to your email address with password reset instructions.")}
                </Paragraph>
                <RememberPasswordLink />
            </div>
        );
    }

    return (
        <div className="authenticateUserCol">
            {pageTitle}
            <Paragraph className="authenticateUser-paragraph">
                {t("RecoverPasswordLabelCode", "Enter your email to continue.")}
            </Paragraph>
            <form
                onSubmit={event => {
                    event.preventDefault();
                    event.stopPropagation();
                    resetPassword({ email });
                }}
                aria-labelledby={pageTitleID}
                noValidate
            >
                <Paragraph className="authenticateUser-paragraph" isError={true}>
                    {getGlobalErrorMessage(error, ["email"])}
                </Paragraph>
                <InputTextBlock
                    label={t("Email")}
                    inputProps={{
                        required: true,
                        value: email,
                        onChange: event => setEmail(event.target.value),
                        disabled: !allowEdit,
                    }}
                    errors={getFieldErrors(error, "email")}
                    ref={emailRef}
                />
                <ButtonSubmit disabled={!allowEdit || email.length === 0} legacyMode={true}>
                    {t("Request a new password")}
                </ButtonSubmit>
            </form>
            <RememberPasswordLink />
        </div>
    );
}
