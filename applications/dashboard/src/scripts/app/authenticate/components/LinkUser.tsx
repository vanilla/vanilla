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
import LinkUserSignIn from "./LinkUserSignIn";

interface IProps {
    step?: string;
    config: any;
    ssoUser: any;
    authSessionID: string;
    location?: any;
    setErrorState: any;
    rememberMe?: boolean;
    termsOfServiceLabel: string;
    termsOfService?: boolean;
    termsOfServiceError?: string;
    globalError?: string;
    emailError?: string;
    nameError?: string;
    password?: string;
    username?: string;
    signInWithField?: string;
}

interface IState extends IRequiredComponentID {
    step?: string;
    editable: boolean;
    globalError?: string | null;
    submitEnabled: boolean;
    rememberMe: boolean;
    termsOfServiceLabel: string;
    termsOfService: boolean;
    termsOfServiceError?: string;
    nameError: string | null;
    emailError: string | null;
    password?: string;
    username?: string;
    signInWithField?: string;
}

export default class LinkUser extends React.Component<IProps, IState> {
    private email: InputTextBlock;
    private name: InputTextBlock;
    private username: InputTextBlock;
    private password: InputTextBlock;
    private rememberMeRef: Checkbox;
    private termsOfServiceElement: Checkbox;

    constructor(props) {
        super(props);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);
        this.handleTermsOfServiceCheckChange = this.handleTermsOfServiceCheckChange.bind(this);
        this.handleRememberMeCheckChange = this.handleRememberMeCheckChange.bind(this);
        this.handleErrors = this.handleErrors.bind(this);
        this.setStepToRegister = this.setStepToRegister.bind(this);
        this.setStepToPasswordWithEmail = this.setStepToPasswordWithEmail.bind(this);
        this.setStepToPasswordWithUsername = this.setStepToPasswordWithUsername.bind(this);
        this.setRememberMeCheck = this.setRememberMeCheck.bind(this);
        this.setTermsOfServiceCheck = this.setTermsOfServiceCheck.bind(this);

        this.state = {
            id: getRequiredID(props, "linkUserRegister"),
            editable: true,
            submitEnabled: false,
            rememberMe: props.rememberMe || false,
            emailError: props.emailError,
            nameError: props.nameError,
            globalError: props.globalError,
            step: "register",
            password: props.password,
            username: props.username,
            termsOfServiceLabel: props.termsOfServiceLabel,
            termsOfService: props.termsOfService || false,
            termsOfServiceError: props.termsOfServiceError,
            signInWithField: props.signInWithField,
        };
    }

    public handleTextChange = event => {
        const type: string = get(event, "target.type", "");
        if (type === "email") {
            // username
            this.setState({
                globalError: null,
                emailError: null,
            });
        }
        if (type === "text") {
            this.setState({
                globalError: null,
                nameError: null,
            });
        }
    };

    public handleTermsOfServiceCheckChange = event => {
        this.setState({
            termsOfService: get(event, "target.checked", false),
        });
    };

    public setTermsOfServiceCheck(value) {
        this.setState({
            termsOfService: value,
        });
    }

    public handleRememberMeCheckChange = event => {
        this.setState({
            rememberMe: get(event, "target.checked", false),
        });
    };

    public setRememberMeCheck(value) {
        this.setState({
            rememberMe: value,
        });
    }

    public handleErrors = error => {
        const catchAllErrorMessage = t("An error has occurred, please try again.");

        const data = error.response.data;
        log("data: ", data);

        let globalError = get(data, "message", false);
        const errors = get(error, "response.data.errors", []);
        const hasFieldSpecificErrors = errors.length > 0;
        let emailError;
        let nameError;
        let termsOfServiceError;

        if (hasFieldSpecificErrors) {
            errors.forEach((error, index) => {
                error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                const genericFieldError = t("This %s is already taken. Enter another %s ");
                const genericInvalidFieldError = t("This %s is not valid. Enter another %s ");
                if (error.field === "email") {
                    if (error.code === "The email is taken.") {
                        emailError = genericFieldError.split("%s").join("email");
                    } else {
                        emailError = error.message;
                    }
                } else if (error.field === "name") {
                    nameError = error.message;
                    if (error.code === "The username is taken.") {
                        nameError = genericFieldError.split("%s").join("name");
                    } else if (error.code === "Username is not valid.") {
                        nameError = genericInvalidFieldError.split("%s").join("name");
                    } else {
                        nameError = error.message;
                    }
                } else if (error.field === "agreeToTerms") {
                    termsOfServiceError = error.message;
                } else {
                    // Unhandled error
                    globalError = catchAllErrorMessage;
                    log("LinkUserRegister - Unhandled error field", error);
                }
            });
        } else {
            // Something went really wrong. Add default message to tell the user there's a problem.
            logError("LinkUserRegister - Failure to handle errors from response -", e);
            globalError = catchAllErrorMessage;
        }
        this.setErrors(globalError, emailError, nameError, termsOfServiceError);
    };

    public setErrors(globalError, emailError: string, nameError: string, termsOfServiceError: string) {
        this.setState(
            {
                editable: true,
                globalError,
                emailError,
                nameError,
                termsOfServiceError,
            },
            () => {
                const hasEmailError = this.state.emailError && !this.props.config.noEmail;
                const hasNameError = this.state.nameError;
                const hasAgreeToTermsError = this.state.termsOfServiceError;

                if (hasEmailError) {
                    this.email.select();
                } else if (hasNameError) {
                    this.name.select();
                } else if (hasAgreeToTermsError) {
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

        const formData: any = {
            authSessionID: this.props.authSessionID,
            agreeToTerms: this.state.termsOfService,
            persist: this.state.rememberMe,
        };

        if (this.state.step === "password") {
            formData.method = "password";
            formData.password = this.password.value;
        } else {
            formData.method = "register";
            formData.email = this.email.value;
            formData.name = this.name.value;
        }

        apiv2
            .post("/authenticate/link-user", formData)
            .then(response => {
                const targetUrl = formatUrl(response.data.targetUrl || "/");
                window.location.href = targetUrl;
            })
            .catch(e => {
                this.handleErrors(e);
            });
    };

    public handleCheckBoxChange = value => {
        this.setState({
            rememberMe: value,
        });
    };

    public setStepToPasswordWithEmail = e => {
        e.preventDefault();
        this.setState({
            step: "password",
            signInWithField: "email",
        });
    };

    public setStepToPasswordWithUsername = e => {
        e.preventDefault();
        this.setState({
            step: "password",
            signInWithField: "name",
        });
    };

    public setStepToRegister = () => {
        this.setState({
            step: "register",
        });
    };

    public setStepToError = () => {
        this.setState({
            step: "error",
        });
    };

    public render() {
        let contents;

        log("Link User step: ", this.state.step);

        if (this.state.step === "register") {
            const linkText = t(" click here to enter your %s.");

            let emailField; // register step

            // for step is register
            emailField = this.props.config.noEmail ? null : (
                <InputTextBlock
                    label={t("Email")}
                    type="email"
                    required={true}
                    disabled={!this.state.editable}
                    errorComponent={ErrorOrLinkLabel}
                    errorComponentData={{
                        errors: this.props.nameError,
                        linkOnClick: this.setStepToPasswordWithEmail,
                        linkText: linkText.replace("%s", t("email")),
                        error: this.state.emailError,
                    }}
                    defaultValue={this.props.ssoUser.email}
                    onChange={this.handleTextChange}
                    ref={email => (this.email = email as InputTextBlock)}
                />
            );

            const globalError: any = this.state.globalError ? (
                <span dangerouslySetInnerHTML={{ __html: this.state.globalError }} />
            ) : null;

            contents = (
                <form className="linkUserRegisterForm" method="post" onSubmit={this.handleSubmit} noValidate>
                    <Paragraph
                        className="authenticateUser-paragraph"
                        content={t("Fill out the following information to complete your registration")}
                    />
                    <Paragraph className="authenticateUser-paragraph" content={globalError} isError={true} />
                    {emailField}
                    <InputTextBlock
                        label={t("Username")}
                        required={true}
                        disabled={!this.state.editable}
                        errorComponent={ErrorOrLinkLabel}
                        errorComponentData={{
                            errors: this.props.nameError,
                            linkOnClick: this.setStepToPasswordWithUsername,
                            linkText: linkText.replace("%s", t("password")),
                            error: this.state.nameError,
                        }}
                        defaultValue={this.props.ssoUser.name}
                        onChange={this.handleTextChange}
                        ref={name => (this.name = name as InputTextBlock)}
                    />

                    <div className="inputBlock inputBlock-tighterTop">
                        <Checkbox
                            label={t("Keep me signed in")}
                            onChange={this.handleRememberMeCheckChange}
                            checked={this.state.rememberMe}
                        />
                        <Checkbox
                            dangerousLabel={this.props.termsOfServiceLabel}
                            onChange={this.handleTermsOfServiceCheckChange}
                            checked={this.state.termsOfService}
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
            );
        } else {
            let userName;
            if (get(this, "state.signInWithField", "") === "name" && this.props.config.nameUnique) {
                userName = get(this, "name.value", "");
            } else {
                userName = get(this, "email.value", get(this.props, "ssoUser.email", ""));
            }

            let userNameLabel = t("Email"); // Fallback to e-mail only
            if ((this.props.config.noEmail || !this.props.config.emailUnique) && this.props.config.nameUnique) {
                // Only name is unique
                userNameLabel = t("Username");
            } else if (!this.props.config.noEmail && this.props.config.emailUnique && this.props.config.nameUnique) {
                // Both email and username are unique
                userNameLabel = t("Email / Username");
            }

            contents = (
                <LinkUserSignIn
                    authSessionID={this.props.authSessionID}
                    setErrorState={this.setStepToError}
                    rememberMe={this.state.rememberMe}
                    username={userName}
                    usernameLabel={userNameLabel}
                    handleBackClick={this.setStepToRegister}
                    termsOfServiceError={this.state.termsOfServiceError}
                    termsOfServiceLabel={this.props.termsOfServiceLabel}
                    termsOfService={this.state.termsOfService}
                    handleTermsOfServiceCheckChange={this.setTermsOfServiceCheck}
                    handleRememberMeCheckChange={this.setRememberMeCheck}
                />
            );
        }

        return <div className="linkUserRegister">{contents}</div>;
    }
}
