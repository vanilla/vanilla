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
import ErrorMessages from "@dashboard/components/forms/ErrorMessages";

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
    termsOfServiceError?: string[];
    globalError?: string;
    emailErrors?: string[];
    nameErrors?: string[];
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
    termsOfServiceError: string[] | null;
    nameErrors: string[] | null;
    emailErrors: string[] | null;
    password?: string;
    username?: string;
    signInWithField?: string;
    termsOfServiceId: string;
    errorComponentEmail: any | null;
    errorComponentUsername: any | null;
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
            emailErrors: props.emailErrors || [],
            nameErrors: props.nameErrors || [],
            globalError: props.globalError,
            step: "register",
            password: props.password,
            username: props.username,
            termsOfServiceLabel: props.termsOfServiceLabel,
            termsOfService: props.termsOfService || false,
            termsOfServiceError: props.termsOfServiceError || [],
            signInWithField: props.signInWithField,
            termsOfServiceId: getRequiredID(props, "termsOfService"),
            errorComponentEmail: null,
            errorComponentUsername: null,
        };
    }

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
        const data = error.response.data || {};
        const globalError = data.message || t("An error has occurred, please try again.");
        const errors = data.errors || [];
        const hasFieldSpecificErrors = errors.length > 0;
        let emailErrors: string[] = [];
        let nameErrors: string[] = [];
        let termsOfServiceError: string[] = [];
        const genericFieldError = t("This %s is already taken. Enter another %s or");
        const genericInvalidFieldError = t("This %s is not valid. Enter another %s or");
        let errorComponentEmail: any = null;
        let errorComponentUsername: any = null;

        log(data);

        if (hasFieldSpecificErrors) {
            errors.forEach(fieldError => {
                fieldError.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                if (fieldError.field === "email") {
                    if ((fieldError.status || data.status) === 409) {
                        errorComponentEmail = ErrorOrLinkLabel;
                        emailErrors = [genericFieldError.split("%s").join("email")];
                    } else {
                        emailErrors = [...emailErrors, fieldError];
                    }
                } else if (fieldError.field === "name") {
                    if ((fieldError.status || data.status) === 409) {
                        errorComponentUsername = ErrorOrLinkLabel;
                        nameErrors = [genericFieldError.split("%s").join("name")];
                    } else {
                        nameErrors = [...nameErrors, fieldError];
                    }
                } else if (fieldError.field === "agreeToTerms") {
                    termsOfServiceError = [...termsOfServiceError, fieldError];
                } else {
                    // Unhandled error
                    log("LinkUserRegister - Unhandled error field", fieldError);
                }
            });
        } else {
            // Something went really wrong. Add default message to tell the user there's a problem.
            logError("LinkUserRegister - Failure to handle errors from response -", error);
        }
        this.setErrors(
            globalError,
            emailErrors,
            nameErrors,
            termsOfServiceError,
            errorComponentEmail,
            errorComponentUsername,
        );
    };

    public setErrors(
        globalError,
        emailErrors: string[],
        nameErrors: string[],
        termsOfServiceError: string[],
        errorComponentEmail: any,
        errorComponentUsername: any,
    ) {
        this.setState(
            {
                editable: true,
                globalError,
                emailErrors,
                nameErrors,
                termsOfServiceError,
                errorComponentEmail,
                errorComponentUsername,
            },
            () => {
                const hasEmailError = this.state.emailErrors && !this.props.config.noEmail;
                const hasNameError = this.state.nameErrors;
                const hasAgreeToTermsError = this.state.termsOfServiceError;

                if (hasEmailError) {
                    this.email.select();
                } else if (hasNameError) {
                    this.name.select();
                } else if (hasAgreeToTermsError) {
                    this.termsOfServiceElement.focus();
                }

                log("Link user state: ", this.state);
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

    public errorID(inputId): string {
        return inputId + "-errors";
    }

    public render() {
        let contents;

        if (this.state.step === "register") {
            const linkText = t(" click here to enter your password.");

            let emailField; // register step

            // for step is register
            emailField = this.props.config.noEmail ? null : (
                <InputTextBlock
                    label={t("Email")}
                    type="email"
                    required={true}
                    disabled={!this.state.editable}
                    errors={this.state.emailErrors as string[]}
                    errorComponent={this.state.errorComponentEmail}
                    errorComponentData={{
                        errors: this.props.nameErrors,
                        linkOnClick: this.setStepToPasswordWithEmail,
                        linkText: linkText.replace("%s", t("email")),
                        error: this.state.emailErrors,
                    }}
                    defaultValue={this.props.ssoUser.email}
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
                        errors={this.state.emailErrors as string[]}
                        errorComponent={this.state.errorComponentUsername}
                        errorComponentData={{
                            errors: this.props.nameErrors,
                            linkOnClick: this.setStepToPasswordWithUsername,
                            linkText: linkText.replace("%s", t("password")),
                            error: this.state.nameErrors,
                        }}
                        defaultValue={this.props.ssoUser.name}
                        ref={name => (this.name = name as InputTextBlock)}
                    />

                    <div className="inputBlock inputBlock-tighterTop">
                        <Checkbox
                            label={t("Keep me signed in")}
                            onChange={this.handleRememberMeCheckChange}
                            checked={this.state.rememberMe}
                        />
                        <Checkbox
                            id={this.state.termsOfServiceId}
                            dangerousLabel={this.props.termsOfServiceLabel}
                            onChange={this.handleTermsOfServiceCheckChange}
                            checked={this.state.termsOfService}
                            ref={termsOfServiceElement =>
                                (this.termsOfServiceElement = termsOfServiceElement as Checkbox)
                            }
                        />

                        <ErrorMessages
                            id={this.errorID(this.state.termsOfServiceId)}
                            errors={this.state.termsOfServiceError as string[]}
                            className="authenticateUser-paragraph"
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
            if (this.props.config.noEmail || (!this.props.config.emailUnique && this.props.config.nameUnique)) {
                // Only name is unique
                userNameLabel = t("Username");
            } else if (!this.props.config.noEmail && this.props.config.emailUnique && this.props.config.nameUnique) {
                // Both email and username are unique
                userNameLabel = t("Email/Username");
            }

            contents = (
                <LinkUserSignIn
                    authSessionID={this.props.authSessionID}
                    setErrorState={this.setStepToError}
                    rememberMe={this.state.rememberMe}
                    username={userName}
                    usernameLabel={userNameLabel}
                    handleBackClick={this.setStepToRegister}
                    termsOfServiceError={this.state.termsOfServiceError as any}
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
