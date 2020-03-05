/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getFieldErrors, getGlobalErrorMessage } from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import React, { useReducer, useRef, useEffect } from "react";
import { withRouter, Link } from "react-router-dom";
import InputTextBlock from "@library/forms/InputTextBlock";
import Checkbox from "@library/forms/Checkbox";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import Paragraph from "@library/layout/Paragraph";
import { IRequiredComponentID, getRequiredID, useUniqueID } from "@library/utility/idUtils";
import { IStoreState, IPasswordState } from "@dashboard/@types/state";
import { connect } from "react-redux";
import { LoadStatus } from "@library/@types/api/core";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import classNames from "classnames";
import { useAuthActions } from "@dashboard/auth/AuthActions";
import { useAuthStoreState } from "@dashboard/auth/authReducer";

interface IProps {
    defaultUsername?: string;
}

interface IForm {
    rememberMe: boolean;
    username: string;
    password: string;
}

const initialForm: IForm = {
    rememberMe: true,
    username: "",
    password: "",
};

/**
 * Username + password from for signins.
 */
export function PasswordForm(props: IProps) {
    const [formState, updateForm] = usePasswordForm();
    const { loginWithPassword } = useAuthActions();
    const { signin: signinState } = useAuthStoreState();

    const usernameRef = useRef<InputTextBlock | null>(null);
    const passwordRef = useRef<InputTextBlock | null>(null);

    const formDescriptionID = useUniqueID("passwordFormDescription");

    let formDescribedBy;
    const globalErrorMessage = getGlobalErrorMessage(signinState.error, ["username", "password"]);
    const classes = inputBlockClasses();
    if (globalErrorMessage) {
        formDescribedBy = formDescriptionID;
    }

    const allowEdit = signinState.status !== LoadStatus.LOADING;
    const allowSubmit = formState.username.length > 0 && formState.password.length > 0;

    // Focus input elements when they recieve a validation error.
    const usernameErrors = getFieldErrors(signinState.error, "username");
    const passwordErrors = getFieldErrors(signinState.error, "password");
    useEffect(() => {
        if (usernameErrors) {
            usernameRef.current!.select();
        } else if (passwordErrors) {
            passwordRef.current!.select();
        } else {
            usernameRef.current!.select();
        }
    }, [usernameErrors, passwordErrors]);

    return (
        <form
            aria-describedby={formDescribedBy}
            className="passwordForm"
            method="post"
            onSubmit={event => {
                event.preventDefault();
                event.stopPropagation();
                loginWithPassword({
                    username: formState.username,
                    password: formState.password,
                    persist: formState.rememberMe,
                });
            }}
            noValidate
        >
            <Paragraph id={formDescriptionID} className="authenticateUser-paragraph" isError={true}>
                {globalErrorMessage}
            </Paragraph>
            <InputTextBlock
                label={t("Email/Username")}
                errors={usernameErrors}
                ref={usernameRef}
                inputProps={{
                    required: true,
                    disabled: !allowEdit,
                    onChange: event => {
                        updateForm({ username: event.target.value });
                    },
                    value: formState.username,
                }}
            />
            <InputTextBlock
                label={t("Password")}
                ref={passwordRef}
                errors={passwordErrors}
                inputProps={{
                    required: true,
                    disabled: !allowEdit,
                    type: "password",
                    onChange: event => {
                        updateForm({ password: event.target.value });
                    },
                    value: formState.password,
                }}
            />
            <div className={classNames(classes.root)}>
                <div className="rememberMeAndForgot">
                    <span className="rememberMeAndForgot-rememberMe">
                        <Checkbox
                            label={t("Keep me signed in")}
                            onChange={event => {
                                updateForm({ rememberMe: event.target.checked || false });
                            }}
                            checked={formState.rememberMe}
                        />
                    </span>
                    <span className="rememberMeAndForgot-forgot">
                        <Link to="/authenticate/recoverpassword">{t("Forgot your password?")}</Link>
                    </span>
                </div>
            </div>
            <ButtonSubmit disabled={!allowSubmit} legacyMode={true}>
                {t("Sign In")}
            </ButtonSubmit>
        </form>
    );
}

function usePasswordForm() {
    return useReducer((nextState: IForm, action: Partial<IForm>) => {
        return {
            ...nextState,
            ...action,
        };
    }, initialForm);
}
