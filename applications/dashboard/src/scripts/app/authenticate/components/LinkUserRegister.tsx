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

interface IProps {
    location?: any;
    globalError?: string;
    usernameError?: string;
    emailError?: string;
    termsOfServiceLabel: string;
    config: any;
    ssoUser: any;
    setErrorState: any;
    acceptedTermsOfService?: boolean;
    rememberMe?: boolean;
    termsOfServiceError?: string;
    authSessionID: string;
}

interface IState extends IRequiredComponentID {
    editable: boolean;
    usernameRef?: InputTextBlock;
    usernameError: string | null;
    emailRef?: InputTextBlock | null;
    emailError: string | null;
    globalError?: string | null;
    submitEnabled: boolean;
    rememberMe: boolean;
    acceptedTermsOfService: boolean;
    termsOfServiceError?: string;
}

export default class SsoUser extends React.Component<IProps, IState> {
    private email: InputTextBlock;
    private username: InputTextBlock;
    private rememberMeElement: Checkbox;
    private termsOfServiceElement: Checkbox;

    constructor(props) {
        super(props);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);
        this.handleTOSCheckChange = this.handleTOSCheckChange.bind(this);
        this.handleRememberMeCheckChange = this.handleRememberMeCheckChange.bind(this);
        this.handleErrors = this.handleErrors.bind(this);
        this.getErrorStringFromErrorObject = this.getErrorStringFromErrorObject.bind(this);

        this.state = {
            id: getRequiredID(props, "linkUserRegister"),
            editable: true,
            submitEnabled: false,
            rememberMe: props.rememberMe || false,
            acceptedTermsOfService: props.acceptedTermsOfService || false,
            emailError: props.emailError,
            usernameError: props.usernameError,
            globalError: props.globalError,
            termsOfServiceError: props.termsOfServiceError,
        };
    }

    public handleTextChange = event => {
        const type: string = get(event, "target.type", "");

        if (type === "email") {
            this.setState({
                globalError: null,
                emailError: null,
            });
        }
        if (type === "text") {
            this.setState({
                globalError: null,
                usernameError: null,
            });
        }
    };

    public handleTOSCheckChange = event => {
        this.setState({
            acceptedTermsOfService: get(event, "target.checked", false),
        });
    };
    public handleRememberMeCheckChange = event => {
        this.setState({
            rememberMe: get(event, "target.checked", false),
        });
    };

    public getErrorStringFromErrorObject(errors) {
        if (errors) {
            const error = errors.map((e, index) => {
                return get(e, "message", null);
            });
            return error.join(". ");
        } else {
            return null;
        }
    }

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
        let emailError;
        let usernameError;

        if (globalError || hasFieldSpecificErrors) {
            if (hasFieldSpecificErrors) {
                globalError = ""; // Only show global error if all fields are error free
                logError("LinkUserRegister Errors", errors);
                errors.forEach((error, index) => {
                    error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                    const genericFieldError = t("This %s is already taken. Enter another %s ");
                    if (error.field === "email") {
                        if (error.code === "The email is taken.") {
                            emailError = genericFieldError.split("%s").join("email");
                        } else {
                            emailError = error.message;
                        }
                    } else if (error.field === "name") {
                        usernameError = error.message;
                        if (error.code === "The username is taken.") {
                            usernameError = genericFieldError.split("%s").join("username");
                        } else {
                            usernameError = error.message;
                        }
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
        this.setErrors(globalError, emailError, usernameError);
    };

    public setErrors(globalError, emailError: string, usernameError: string) {
        this.setState(
            {
                editable: true,
                globalError,
                emailError,
                usernameError,
            },
            () => {
                const hasGlobalError = !!this.state.globalError;
                const hasEmailError = this.state.emailError && !this.props.config.noEmail;
                const hasUsernameError = get(this, "state.usernameErrors", []).length > 0;

                if (hasGlobalError && !hasEmailError && !hasUsernameError) {
                    this.username.select();
                } else if (hasUsernameError) {
                    this.username.select();
                } else if (hasEmailError) {
                    this.email.select();
                }
            },
        );
    }

    public validateForm(): boolean {
        let valid = false;

        if (!this.state.acceptedTermsOfService) {
            this.setState({
                termsOfServiceError: t("You must agree to the terms of service."),
            });
        } else {
            valid = true;
        }

        if (!valid) {
            log(t("LinkUserRegister Form Valid?: "), valid);
        }

        return valid;
    }

    public handleSubmit = event => {
        event.preventDefault();

        if (this.validateForm()) {
            this.setState({
                editable: false,
            });

            // log(t("Do submit:"), {
            //     username: this.username.value,
            //     email: this.email.value,
            //     persist: this.state.rememberMe,
            // });

            apiv2
                .post("/authenticate/link-user", {
                    authSessionID: this.props.authSessionID,
                    method: "register",
                    name: this.username.value,
                    email: this.email.value,
                    agreeToTerms: this.state.acceptedTermsOfService,
                    persist: this.state.rememberMe,
                })
                .then(e => {
                    const data = get(e, "response.data", false);
                    log(t("Pass with data: "), data);
                })
                .catch(e => {
                    this.handleErrors(e);
                });
        }
    };

    public setComponentStep(newState) {
        this.setState({ ...newState });
    }

    public render() {
        const errorComponentData = {
            errors: this.props.usernameError,
            linkOnClick: this.setComponentStep,
        };
        const linkText = t(" click here to enter your %s.");

        const emailField = this.props.config.noEmail ? null : (
            <InputTextBlock
                label={t("Email")}
                type="email"
                required={true}
                disabled={!this.state.editable}
                errorComponent={ErrorOrLinkLabel}
                errorComponentData={{
                    ...errorComponentData,
                    linkText: linkText.replace("%s", t("email")),
                    error: this.state.emailError,
                }}
                defaultValue={this.props.ssoUser.email}
                onChange={this.handleTextChange}
                ref={email => (this.email = email as InputTextBlock)}
            />
        );

        return (
            <div className="linkUserRegister">
                <form className="linkUserRegisterForm" method="post" onSubmit={this.handleSubmit} noValidate>
                    <Paragraph content={t("Fill out the following information to complete your registration")} />
                    {emailField}
                    <InputTextBlock
                        label={t("Username")}
                        required={true}
                        disabled={!this.state.editable}
                        errorComponent={ErrorOrLinkLabel}
                        errorComponentData={{
                            ...errorComponentData,
                            linkText: linkText.replace("%s", t("password")),
                            error: this.state.usernameError,
                        }}
                        defaultValue={this.props.ssoUser.name}
                        onChange={this.handleTextChange}
                        ref={username => (this.username = username as InputTextBlock)}
                    />
                    <div className="inputBlock">
                        <Checkbox
                            dangerousLabel={this.props.termsOfServiceLabel}
                            onChange={this.handleTOSCheckChange}
                            checked={this.state.acceptedTermsOfService}
                            ref={termsOfServiceElement =>
                                (this.termsOfServiceElement = termsOfServiceElement as Checkbox)
                            }
                        />
                        <Paragraph
                            className="authenticateUser-paragraph"
                            isError={true}
                            content={this.state.termsOfServiceError}
                        />
                        <Checkbox
                            label={t("Keep me signed in")}
                            onChange={this.handleRememberMeCheckChange}
                            checked={this.state.rememberMe}
                            ref={rememberMeElement => (this.rememberMeElement = rememberMeElement as Checkbox)}
                        />
                    </div>
                    <ButtonSubmit disabled={!this.state.editable} content={t("Connect")} />
                </form>
            </div>
        );
    }
}
