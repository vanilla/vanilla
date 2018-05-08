import apiv2 from "@core/apiv2";
import { t } from "@core/application";
import { log, logError, debug } from "@core/utility";
import DocumentTitle from "@core/Components/DocumentTitle";
import React from "react";
import { uniqueIDFromPrefix, getOptionalID, IOptionalComponentID } from "@core/Interfaces/componentIDs";
import ButtonSubmit from "../Forms/ButtonSubmit";
import Paragraph from "../Forms/Paragraph";
import InputTextBlock from "../Forms/InputTextBlock";
import RememberPasswordLink from "./Components/RememberPasswordLink";
import get from "lodash/get";

interface IState {
    editable: boolean;
    emailSent: boolean;
    errors?: string[];
    email: string;
    submitEnabled: boolean;
    globalError?: string | null;
}

interface IProps extends IOptionalComponentID {
    globalError?: string;
    errors?: string[];
}

export default class RecoverPasswordPage extends React.Component<IProps, IState> {
    public static getDerivedStateFromProps(nextProps, prevState) {
        prevState.globalError = nextProps.globalError;
        prevState.errors = nextProps.errors;
        return prevState;
    }

    public id: string;
    public pageTitleID: string;
    public email: InputTextBlock;

    constructor(props) {
        super(props);
        this.id = uniqueIDFromPrefix("RecoverPasswordPage");
        this.pageTitleID = this.id + "-pageTitle";
        this.handleTextChange = this.handleTextChange.bind(this);

        this.state = {
            editable: true,
            emailSent: false,
            submitEnabled: false,
            email: "",
            globalError: props.globalError,
            errors: props.errors,
        };
    }

    public handleTextChange = event => {
        const value: string = get(event, "target.value", "");
        this.setState({
            email: value,
            globalError: null,
            errors: [],
        });
    };

    // Disable button when in submit state
    // Error handling from server side messages
    // If errors is empty, use global message, if not ignore and use per input messages

    public handleSubmit = event => {
        event.preventDefault();

        this.setState({
            editable: false,
            submitEnabled: false,
        });

        apiv2
            .post("/users/request-password", {
                email: this.state.email,
            })
            .then(r => {
                this.setState({
                    emailSent: true,
                });
            })
            .catch(e => {
                logError(e.response);
                this.setState(
                    {
                        editable: true,
                    },
                    () => {
                        this.normalizeErrors(e);
                        this.email.select();
                    },
                );
            });
    };

    public normalizeErrors = e => {
        // Reset Errors
        this.setState(
            {
                globalError: null,
                errors: [],
            },
            () => {
                logError(e.response);
                const errors = get(e, "response.data.errors", []);
                const generalError = get(e, "response.data.message", false);
                const fallbackErrorMessage = t("An error has occurred, please try again.");
                const hasFieldSpecificErrors = errors.length > 0;

                if (generalError || hasFieldSpecificErrors) {
                    if (hasFieldSpecificErrors) {
                        // Field Errors

                        logError("SignInForm Errors", errors);

                        const newState: any = {
                            editable: true,
                            errors: [],
                        };

                        errors.map((error: any, index) => {
                            error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                            newState.errors.push(error);
                        });

                        this.setState(newState);
                    } else {
                        // Global message
                        this.setState({
                            globalError: generalError,
                        });
                    }
                } else {
                    // Something went really wrong. Add default message to tell the user there's a problem.
                    this.setState({
                        globalError: fallbackErrorMessage,
                    });
                }
            },
        );
    };

    public render() {
        const pageTitle = (
            <DocumentTitle title={t("Recover Password")}>
                <h1 id={this.pageTitleID} className="isCentered">
                    {t("Recover Password")}
                </h1>
            </DocumentTitle>
        );

        if (this.state.emailSent) {
            return (
                <div id={this.id} className="authenticateUserCol">
                    {pageTitle}
                    <Paragraph
                        content={t("A message has been sent to your email address with password reset instructions.")}
                        className="authenticateUser-paragraph"
                    />
                    <RememberPasswordLink />
                </div>
            );
        } else {
            return (
                <div className="authenticateUserCol">
                    {pageTitle}
                    <Paragraph
                        content={t("RecoverPasswordLabelCode", "Enter your email to continue.")}
                        className="authenticateUser-paragraph"
                    />
                    <form id={this.id} onSubmit={this.handleSubmit} aria-labelledby={this.pageTitleID} noValidate>
                        <Paragraph
                            className="authenticateUser-paragraph"
                            content={this.state.globalError}
                            isError={true}
                        />
                        <InputTextBlock
                            label={t("Email/Username")}
                            required={true}
                            disabled={!this.state.editable}
                            errors={this.state.errors}
                            value={this.state.email}
                            onChange={this.handleTextChange}
                            ref={email => (this.email = email as InputTextBlock)}
                        />
                        <ButtonSubmit
                            disabled={!this.state.editable || this.state.email.length === 0}
                            content={t("Request a new password")}
                        />
                    </form>
                    <RememberPasswordLink />
                </div>
            );
        }
    }
}
