import apiv2 from "@core/apiv2";
import { formatUrl, t } from '@core/application';
import React from 'react';
import { withRouter, BrowserRouter, Route } from 'react-router-dom';
import { log, logError, debug } from "@core/utility";
import InputTextBlock from "../../Forms/InputTextBlock";
import PasswordTextBlock from "../../Forms/PasswordTextBlock";
import ButtonSubmit from "../../Forms/ButtonSubmit";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import CreateAnAccountLink from "./CreateAnAccountLink";
import Paragraph from "../../Forms/Paragraph";
import { get } from "lodash/get";

interface IProps {
    location?: any;
}

interface IState {
    editable: boolean;
    username: string;
    usernameErrors?: string[];
    password: string;
    passwordErrors?: string[];
    redirectTo?: string | null;
    genericError?: string | null;
    submitEnabled: boolean;
}

class SignInForm extends React.Component<IProps, IState> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueIDFromPrefix('signInForm');
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);

        this.state = {
            editable: true,
            username: '',
            password: '',
            redirectTo: null,
            submitEnabled: false,
        };
    }

    public handleTextChange = (event) => {
        const type = event.target.type;
        const value = event.target.value;

        if (type === 'password') {
            this.setState({
                password: value,
                genericError: null,
            }, this.isSubmitEnabled);
        } else {
            this.setState({
                username: value,
                genericError: null,
            }, this.isSubmitEnabled);
        }
    }

    public normalizeErorrs = (errors) => {
        log("Errors: ", errors);

        // Reset Errors
        this.setState({
            genericError: null,
            passwordErrors: [],
            usernameErrors: []
        });

        // if ()
        // field: "username", code: "missingfield", "message": "password is required"
        // message

        const genericErrorMessage = t('An error has occured, please try again.');
        const hasFieldSpecificErrors = errors && errors.errors && errors.errors.length > 0;
        const hasGenericError = errors && errors.message;

        if (hasGenericError || hasFieldSpecificErrors) {
            if (hasFieldSpecificErrors) { // Field Errors
                errors.errors.map((error, index) => {
                    error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                    this.state[error.field + 'Errors'].push(error);
                });
            } else { // Global message
                this.setState({
                    genericError: errors.message,
                });
            }
        } else { // Something went really wrong. Add default message to tell the user there's a problem.
            this.setState({
                genericError: genericErrorMessage,
            });
        }
    }

    public handleSubmit = (event) => {
        event.preventDefault();

        this.setState({
            editable: false
        });

        apiv2.post('/authenticate/password', {
            username: this.state.username,
            password: this.state.password,
        }).then((r) => {
            window.location.href = get(this, 'props.location.query.target', '/');
        }).catch((e) => {
            logError("Sign In Form error loggin in: ", e.response.data);
            this.setState({
                editable: true,
            }, () => {
                this.normalizeErorrs(e.errors);
            });
        });
    }

    public isSubmitEnabled = () => {
        this.setState({
            submitEnabled: this.state.editable && this.state.password.length > 0 && this.state.username.length > 0,
        });
    }

    public render() {
        if (this.state.redirectTo) {
            return <BrowserRouter>
                <Route path={this.state.redirectTo} component={SignInForm} />
            </BrowserRouter>;
        } else {
            return <form id={this.ID} className="signInForm" method="post" onSubmit={this.handleSubmit} noValidate>
                <Paragraph parentID={this.ID} className="authenticateUser-paragraph" content={this.state.genericError} isError={true} />
                <InputTextBlock
                    parentID={this.ID}
                    label={t('Email/Username')}
                    placeholder={t('Enter Username')}
                    required={true}
                    disabled={!this.state.editable}
                    errors={this.state.usernameErrors}
                    value={this.state.username}
                    onChange={this.handleTextChange}
                />
                <PasswordTextBlock
                    parentID={this.ID}
                    label={t('Password')}
                    placeholder={t('Enter Password')}
                    required={true}
                    disabled={!this.state.editable}
                    errors={this.state.passwordErrors}
                    value={this.state.password}
                    onChange={this.handleTextChange}
                />
                <ButtonSubmit parentID={this.ID} disabled={this.state.submitEnabled} content={t('Sign In')}/>
                <CreateAnAccountLink/>
            </form>;
        }
    }
}

export default withRouter(SignInForm);
