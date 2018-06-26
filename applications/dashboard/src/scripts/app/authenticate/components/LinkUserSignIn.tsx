/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { formatUrl, t } from "@dashboard/application";
import React from "react";
import Paragraph from "@dashboard/components/forms/Paragraph";
import { log, logError } from "@dashboard/utility";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import apiv2 from "@dashboard/apiv2";
import { getRequiredID, IRequiredComponentID } from "@dashboard/componentIDs";
import get from "lodash/get";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Checkbox from "@dashboard/components/forms/Checkbox";
import RememberAndForgotPassword from "@dashboard/app/authenticate/components/RememberAndForgotPassword";
import BackLink from "@dashboard/app/authenticate/components/BackLink";

interface IProps {
    authSessionID: string;
    setErrorState: any;
    rememberMe?: boolean;
    termsOfService?: boolean;
    globalError?: string;
    username?: string;
    usernameError?: string[];
    password?: string;
    passwordError?: string[];
    handleBackClick: any;
    usernameLabel: string;

    termsOfServiceError?: string;
    termsOfServiceLabel: string;
    handleTermsOfServiceCheckChange: any;
    handleRememberMeCheckChange: any;
}

interface IState extends IRequiredComponentID {
    editable: boolean;
    globalError?: string;
    submitEnabled: boolean;
    password?: string;
    passwordError: string[];
    username?: string;
    usernameError: string[];
    termsOfServiceError: string[];
}

export default class LinkUserSignIn extends React.Component<IProps, IState> {
    private username: InputTextBlock;
    private password: InputTextBlock;
    private termsOfServiceElement: Checkbox;

    constructor(props) {
        super(props);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleErrors = this.handleErrors.bind(this);
        this.handleTermsOfServiceCheckChange = this.handleTermsOfServiceCheckChange.bind(this);

        this.state = {
            id: getRequiredID(props, "linkUserRegister"),
            editable: true,
            submitEnabled: false,
            globalError: props.globalError,
            termsOfServiceError: props.termsOfServiceError,
            password: props.password,
            passwordError: props.passwordError || null,
            username: props.username,
            usernameError: props.usernameError || null,
        };
    }

    public handleErrors = error => {
        const data = error.response.data || {};
        const globalError = data.message || t("An error has occurred, please try again.");
        const errors = data.errors || [];
        const hasFieldSpecificErrors = errors.length > 0;
        let usernameError: string[] = [];
        let passwordError: string[] = [];
        let termsOfServiceError: string[] = [];

        if (hasFieldSpecificErrors) {
            errors.forEach((fieldError, index) => {
                fieldError.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                const genericFieldError = t("This %s is already taken. Enter another %s ");
                if (fieldError.field === "username") {
                    usernameError = [...usernameError, fieldError];
                } else if (fieldError.field === "password") {
                    passwordError = [...passwordError, fieldError];
                } else if (fieldError.field === "termsofservice") {
                    termsOfServiceError = [...termsOfServiceError, fieldError];
                } else {
                    // Unhandled error
                    logError("LinkUserSignIn - Unhandled error field", fieldError);
                }
            });
        }
        this.setErrors(globalError, usernameError, passwordError, termsOfServiceError);
    };

    public handleSubmit = event => {
        event.preventDefault();

        this.setState({
            editable: false,
        });

        apiv2
            .post("/authenticate/link-user", {
                authSessionID: this.props.authSessionID,
                agreeToTerms: !!this.props.termsOfService,
                persist: !!this.props.rememberMe,
                method: "password",
                username: this.username.value,
                password: this.password.value,
            })
            .then(response => {
                const targetUrl = formatUrl(response.data.targetUrl || "/");
                window.location.href = targetUrl;
            })
            .catch(e => {
                this.handleErrors(e);
            });
    };

    public setErrors(globalError, usernameError: string[], passwordError: string[], termsOfServiceError: string) {
        this.setState(
            {
                editable: true,
                globalError,
                usernameError,
                passwordError,
                termsOfServiceError,
            },
            () => {
                const hasUsernameError = this.state.usernameError;
                const hasPasswordError = this.state.passwordError;
                const hasTermsOfServiceError = this.state.termsOfServiceError;

                if (hasUsernameError) {
                    this.username.select();
                } else if (hasPasswordError) {
                    this.password.select();
                } else if (hasTermsOfServiceError) {
                    this.termsOfServiceElement.focus();
                }
            },
        );
    }

    public handleTermsOfServiceCheckChange = event => {
        this.props.handleTermsOfServiceCheckChange(get(event, "target.checked", false));
    };

    public render() {
        log("link user sign in props: ", this.props);
        log("link user sign in state: ", this.state);

        const globalError: any = this.state.globalError ? (
            <span dangerouslySetInnerHTML={{ __html: this.state.globalError }} />
        ) : null;

        return (
            <div className="linkUserRegister">
                <form className="linkUserRegisterForm" method="post" onSubmit={this.handleSubmit} noValidate>
                    <Paragraph content={t("Sign in with your existing account to connect")} />
                    <Paragraph className="authenticateUser-paragraph" content={globalError} isError={true} />
                    <InputTextBlock
                        label={this.props.usernameLabel}
                        required={true}
                        disabled={!this.state.editable}
                        errors={this.state.usernameError as string}
                        defaultValue={this.props.username}
                        ref={username => (this.username = username as InputTextBlock)}
                    />

                    <InputTextBlock
                        label={t("Password")}
                        required={true}
                        disabled={!this.state.editable}
                        errors={this.state.passwordError as string}
                        defaultValue={this.props.password}
                        type="password"
                        ref={password => (this.password = password as InputTextBlock)}
                    />

                    <div className="inputBlock inputBlock-tighterTop">
                        <RememberAndForgotPassword
                            rememberMe={!!this.props.rememberMe}
                            onChange={this.props.handleRememberMeCheckChange}
                        />
                        <Checkbox
                            dangerousLabel={this.props.termsOfServiceLabel}
                            onChange={this.handleTermsOfServiceCheckChange}
                            checked={!!this.props.termsOfService}
                            ref={termsOfServiceElement =>
                                (this.termsOfServiceElement = termsOfServiceElement as Checkbox)
                            }
                        />
                        <Paragraph
                            className="authenticateUser-paragraph"
                            isError={true}
                            content={this.state.termsOfServiceError}
                        />
                    </div>
                    <ButtonSubmit disabled={!this.state.editable} content={t("Connect")} />
                </form>
                <BackLink
                    classNames="linkUser-backLink"
                    iconClasses="backLink-icon"
                    onClick={this.props.handleBackClick}
                />
            </div>
        );
    }
}
