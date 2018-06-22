/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { formatUrl, t } from "@dashboard/application";
import React from "react";
import { withRouter, BrowserRouter, Route, Link } from "react-router-dom";
import Paragraph from "@dashboard/components/forms/Paragraph";
import { log, logError } from "@dashboard/utility";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import apiv2 from "@dashboard/apiv2";
import { getRequiredID, IRequiredComponentID } from "@dashboard/componentIDs";
import get from "lodash/get";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Checkbox from "@dashboard/components/forms/Checkbox";
import ErrorOrLinkLabel from "@dashboard/app/authenticate/components/ErrorOrLinkLabel";
import RememberAndForgotPassword from "@dashboard/app/authenticate/components/RememberAndForgotPassword";
import { chevronLeft } from "./Icons";
import BackLink from "@dashboard/app/authenticate/components/BackLink";

interface IProps {
    authSessionID: string;
    setErrorState: any;
    rememberMe?: boolean;
    globalError?: string;
    username?: string;
    usernameError?: string;
    password?: string;
    passwordError?: string;
    handleBackClick: any;

    termsOfServiceError?: string;
    termsOfServiceLabel: string;
    termsOfService?: boolean;
    handleTermsOfServiceCheckChange: any;
    handleRememberMeCheckChange: any;
}

interface IState extends IRequiredComponentID {
    editable: boolean;
    globalError?: string | null;
    submitEnabled: boolean;
    rememberMe: boolean;
    password?: string;
    passwordError: string | null;
    username?: string | null;
    usernameError?: string | null;
    termsOfServiceError?: string | null;
}

export default class LinkUserSignIn extends React.Component<IProps, IState> {
    private username: InputTextBlock;
    private password: InputTextBlock;
    private rememberMe: Checkbox;
    private termsOfServiceElement: Checkbox;

    constructor(props) {
        super(props);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);
        this.handleErrors = this.handleErrors.bind(this);
        this.handleTermsOfServiceCheckChange = this.handleTermsOfServiceCheckChange.bind(this);

        this.state = {
            id: getRequiredID(props, "linkUserRegister"),
            editable: true,
            submitEnabled: false,
            globalError: props.globalError,
            rememberMe: props.rememberMe || false,
            termsOfServiceError: props.termsOfServiceError,
            password: props.password,
            passwordError: props.passwordError || null,
            username: props.username,
            usernameError: props.usernameError || null,
        };
    }

    public handleTextChange = event => {
        const type: string = get(event, "target.type", "");
        if (type === "text") {
            this.setState({
                username: null,
                usernameError: null,
            });
        }
        if (type === "password") {
            // name
            this.setState({
                globalError: null,
                passwordError: null,
            });
        }
    };

    public handleErrors = e => {
        const catchAllErrorMessage = t("An error has occurred, please try again.");

        const data = get(e, "response.data", false);

        log("data: ", data);

        if (data.errors) {
            log("errors: ", data.errors);
        }

        // this.setState({
        //     editable: true,
        // });

        let globalError = get(e, "response.data.message", false);
        const errors = get(e, "response.data.errors", []);
        const hasFieldSpecificErrors = errors.length > 0;
        let usernameError;
        let passwordError;
        let termsOfServiceError;

        if (globalError || hasFieldSpecificErrors) {
            if (hasFieldSpecificErrors) {
                globalError = ""; // Only show global error if all fields are error free
                errors.forEach((error, index) => {
                    error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                    const genericFieldError = t("This %s is already taken. Enter another %s ");
                    if (error.field === "username") {
                        usernameError = error.message;
                    } else if (error.field === "password") {
                        passwordError = error.message;
                    } else if (error.field === "termsofservice") {
                        termsOfServiceError = error.message;
                    } else {
                        // Unhandled error
                        globalError = catchAllErrorMessage;
                        logError("LinkUserRegister - Unhandled error field", error);
                    }
                });
            }
        } else {
            // Something went really wrong. Add default message to tell the user there's a problem.
            logError("LinkUserRegister - Failure to handle errors from response -", e);
            globalError = catchAllErrorMessage;
        }
        this.setErrors(globalError, usernameError, passwordError, termsOfServiceError);
    };

    public setErrors(globalError, usernameError: string, passwordError: string, termsOfServiceError: string) {
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

    public handleSubmit = event => {
        event.preventDefault();

        this.setState({
            editable: false,
        });

        apiv2
            .post("/authenticate/link-user", {
                authSessionID: this.props.authSessionID,
                agreeToTerms: this.props.termsOfService,
                persist: this.props.termsOfService,
                method: "password",
                username: this.username.value,
                password: this.password.value,
            })
            .then(e => {
                const data = get(e, "response.data", false);
                log(t("Pass with data: "), data);
            })
            .catch(e => {
                this.handleErrors(e);
            });
    };

    public handleTermsOfServiceCheckChange = event => {
        this.props.handleTermsOfServiceCheckChange(get(event, "target.checked", false));
    };
    public handleRememberMeCheckChange = event => {
        this.props.handleRememberMeCheckChange(get(event, "target.checked", false));
    };

    public render() {
        return (
            <div className="linkUserRegister">
                <form className="linkUserRegisterForm" method="post" onSubmit={this.handleSubmit} noValidate>
                    <Paragraph content={t("Sign in with your existing account to connect")} />
                    <InputTextBlock
                        label={t("Email / Username")}
                        required={true}
                        disabled={!this.state.editable}
                        errors={this.state.usernameError as string}
                        defaultValue={this.props.username}
                        onChange={this.handleTextChange}
                        ref={username => (this.username = username as InputTextBlock)}
                    />

                    <InputTextBlock
                        label={t("Password")}
                        required={true}
                        disabled={!this.state.editable}
                        errors={this.state.passwordError as string}
                        defaultValue={this.props.password}
                        onChange={this.handleTextChange}
                        type="password"
                        ref={password => (this.password = password as InputTextBlock)}
                    />

                    <div className="inputBlock">
                        <RememberAndForgotPassword onChange={this.handleRememberMeCheckChange} />
                        <Checkbox
                            dangerousLabel={this.props.termsOfServiceLabel}
                            onChange={this.handleTermsOfServiceCheckChange}
                            checked={this.props.termsOfService}
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
                <BackLink classNames="linkUser-backLink" onClick={this.props.handleBackClick} />
            </div>
        );
    }
}
