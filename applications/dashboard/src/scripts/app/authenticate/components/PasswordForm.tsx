/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import apiv2 from "@dashboard/apiv2";
import { formatUrl, t } from "@dashboard/application";
import React from "react";
import { withRouter, BrowserRouter, Route, Link } from "react-router-dom";
import { log, logError, debug } from "@dashboard/utility";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import Checkbox from "@dashboard/components/forms/Checkbox";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Paragraph from "@dashboard/components/forms/Paragraph";
import get from "lodash/get";
import { IRequiredComponentID, getRequiredID } from "@dashboard/componentIDs";

interface IProps {
    password: string;
    username: string;
    globalError?: string;
    passwordErrors?: string[];
    usernameErrors?: string[];
}

interface IState extends IRequiredComponentID {
    allowEdit: boolean;
    usernameErrors: string[];
    passwordErrors: string[];
    globalError?: string | null;
    allowSubmit: boolean;
    rememberMe: boolean;
}

class PasswordForm extends React.Component<IProps, IState> {
    private usernameInput: InputTextBlock;
    private passwordInput: InputTextBlock;

    constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "passwordForm"),
            allowEdit: true,
            allowSubmit: false,
            rememberMe: true,
            usernameErrors: props.usernameErrors || [],
            passwordErrors: props.passwordErrors || [],
            globalError: props.globalError,
        };
    }

    public handleTextChange = event => {
        const type: string = get(event, "target.type", "");

        if (type === "password") {
            this.setState({
                globalError: null,
                passwordErrors: [],
            });
        }
        if (type === "text") {
            this.setState({
                globalError: null,
                usernameErrors: [],
            });
        }
    };

    public handleCheckBoxChange = event => {
        const value: boolean = get(event, "target.checked", false);
        this.setState({
            rememberMe: value,
        });
    };

    public handleErrors = e => {
        const catchAllErrorMessage = t("An error has occurred, please try again.");
        let globalError = get(e, "response.data.message", false);
        const errors = get(e, "response.data.errors", []);
        const hasFieldSpecificErrors = errors.length > 0;
        let passwordErrors: string[] = [];
        let usernameErrors: string[] = [];

        if (globalError || hasFieldSpecificErrors) {
            if (hasFieldSpecificErrors) {
                globalError = ""; // Only show global error if all fields are error free
                logError("PasswordForm Errors", errors);
                errors.forEach((error, index) => {
                    error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                    if (error.field === "password") {
                        passwordErrors = [...passwordErrors, error];
                    } else if (error.field === "username") {
                        usernameErrors = [...usernameErrors, error];
                    } else {
                        // Unhandled error
                        globalError = catchAllErrorMessage;
                        logError("PasswordForm - Unhandled error field", error);
                    }
                });
            }
        } else {
            // Something went really wrong. Add default message to tell the user there's a problem.
            logError("PasswordForm - Failure to handle errors from response -", e);
            globalError = catchAllErrorMessage;
        }
        this.setErrors(globalError, passwordErrors, usernameErrors);
    };

    public setErrors(globalError, passwordErrors: string[], usernameErrors: string[]) {
        this.setState(
            {
                allowEdit: true,
                passwordErrors,
                usernameErrors,
                globalError,
            },
            () => {
                const hasGlobalError = !!this.state.globalError;
                const hasPasswordError = this.state.passwordErrors.length > 0;
                const hasUsernameError = this.state.usernameErrors.length > 0;

                if (hasGlobalError && !hasPasswordError && !hasUsernameError) {
                    this.usernameInput.select();
                } else if (hasUsernameError) {
                    this.usernameInput.select();
                } else if (hasPasswordError) {
                    this.passwordInput.select();
                }
            },
        );
    }

    public handleSubmit = event => {
        event.preventDefault();

        this.setState({
            allowEdit: false,
        });

        apiv2
            .post("/authenticate/password", {
                username: this.usernameInput.value,
                password: this.passwordInput.value,
                persist: this.state.rememberMe,
            })
            .then(r => {
                const search = get(this, "props.location.search", "/");
                const params = new URLSearchParams(search);
                window.location.href = formatUrl(params.get("target") || "/");
            })
            .catch(e => {
                this.handleErrors(e);
            });
    };

    public get formDescriptionID() {
        return this.state.id + "-description";
    }

    public render() {
        let formDescribedBy;
        if (this.state.globalError) {
            formDescribedBy = this.formDescriptionID;
        }

        return (
            <form
                id={this.state.id}
                aria-describedby={formDescribedBy}
                className="passwordForm"
                method="post"
                onSubmit={this.handleSubmit}
                noValidate
            >
                <Paragraph
                    id={this.formDescriptionID}
                    className="authenticateUser-paragraph"
                    content={this.state.globalError}
                    isError={true}
                />
                <InputTextBlock
                    label={t("Email/Username")}
                    required={true}
                    disabled={!this.state.allowEdit}
                    errors={this.state.usernameErrors}
                    defaultValue={this.props.username}
                    onChange={this.handleTextChange}
                    ref={username => (this.usernameInput = username as InputTextBlock)}
                />
                <InputTextBlock
                    label={t("Password")}
                    required={true}
                    disabled={!this.state.allowEdit}
                    errors={this.state.passwordErrors}
                    defaultValue={this.props.password}
                    onChange={this.handleTextChange}
                    type="password"
                    ref={password => (this.passwordInput = password as InputTextBlock)}
                />
                <div className="inputBlock inputBlock-tighter">
                    <div className="rememberMeAndForgot">
                        <span className="rememberMeAndForgot-rememberMe">
                            <Checkbox
                                label={t("Keep me signed in")}
                                onChange={this.handleCheckBoxChange}
                                checked={this.state.rememberMe}
                            />
                        </span>
                        <span className="rememberMeAndForgot-forgot">
                            <Link to="/authenticate/recoverpassword">{t("Forgot your password?")}</Link>
                        </span>
                    </div>
                </div>
                <ButtonSubmit disabled={!this.state.allowEdit} content={t("Sign In")} />
                {/*<p className="authenticateUser-paragraph isCentered">{t('Not registered?')} <Link to="/entry/signup">{t('Create an Account')}</Link></p>*/}
            </form>
        );
    }
}

export default withRouter(PasswordForm);
