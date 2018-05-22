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
    location?: any;
    password: string;
    username: string;
    globalError?: string;
    passwordErrors?: string[];
    usernameErrors?: string[];
}

interface IState extends IRequiredComponentID {
    editable: boolean;
    usernameRef?: InputTextBlock;
    usernameErrors: string[];
    passwordRef?: InputTextBlock;
    passwordErrors: string[];
    redirectTo?: string | null;
    globalError?: string | null;
    submitEnabled: boolean;
    rememberMe: boolean;
}

class PasswordForm extends React.Component<IProps, IState> {
    public static getDerivedStateFromProps(nextProps, prevState) {
        prevState.usernameErrors = nextProps.usernameErrors;
        prevState.passwordErrors = nextProps.passwordErrors;
        prevState.globalError = nextProps.globalError;
        return prevState;
    }

    private username: InputTextBlock;
    private password: InputTextBlock;

    constructor(props) {
        super(props);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);
        this.handleCheckBoxChange = this.handleCheckBoxChange.bind(this);
        this.handleErrors = this.handleErrors.bind(this);

        this.state = {
            id: getRequiredID(props, "passwordForm"),
            editable: true,
            redirectTo: null,
            submitEnabled: false,
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
                editable: true,
                passwordErrors,
                usernameErrors,
                globalError,
            },
            () => {
                const hasGlobalError = !!this.state.globalError;
                const hasPasswordError = this.state.passwordErrors.length > 0;
                const hasUsernameError = this.state.usernameErrors.length > 0;

                if (hasGlobalError && !hasPasswordError && !hasUsernameError) {
                    this.username.select();
                } else if (hasUsernameError) {
                    this.username.select();
                } else if (hasPasswordError) {
                    this.password.select();
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
            .post("/authenticate/password", {
                username: this.username.value,
                password: this.password.value,
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
        if (this.state.redirectTo) {
            return (
                <BrowserRouter>
                    <Route path={this.state.redirectTo} component={PasswordForm} />
                </BrowserRouter>
            );
        } else {
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
                        disabled={!this.state.editable}
                        errors={this.state.usernameErrors}
                        defaultValue={this.props.username}
                        onChange={this.handleTextChange}
                        ref={username => (this.username = username as InputTextBlock)}
                    />
                    <InputTextBlock
                        label={t("Password")}
                        required={true}
                        disabled={!this.state.editable}
                        errors={this.state.passwordErrors}
                        defaultValue={this.props.password}
                        onChange={this.handleTextChange}
                        type="password"
                        ref={password => (this.password = password as InputTextBlock)}
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
                    <ButtonSubmit disabled={!this.state.editable} content={t("Sign In")} />
                    {/*<p className="authenticateUser-paragraph isCentered">{t('Not registered?')} <Link to="/entry/signup">{t('Create an Account')}</Link></p>*/}
                </form>
            );
        }
    }
}

export default withRouter(PasswordForm);
