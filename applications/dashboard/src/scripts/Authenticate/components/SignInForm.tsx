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
import get from "lodash/get";

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
    globalError?: string | null;
    submitEnabled: boolean;
}

class SignInForm extends React.Component<IProps, IState> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueIDFromPrefix('signInForm');
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);
        this.handleErrors = this.handleErrors.bind(this);

        this.state = {
            editable: true,
            username: '',
            password: '',
            redirectTo: null,
            submitEnabled: false,
        };
    }

    public handleTextChange = (event) => {
        const type:string = get(event, 'target.type', '');
        const value:string = get(event, 'target.value', '');

        if (type === 'password') {
            this.setState({
                password: value,
                globalError: null,
                passwordErrors: [],
            });
        } if (type === 'text') {
            this.setState({
                username: value,
                globalError: null,
                usernameErrors: [],
            });
        }
    }

    public handleErrors = (e) => {
        const errors = get(e, 'response.data.errors', []);
        const generalError = get(e, 'response.data.message', false);
        const catchAllErrorMessage = t('An error has occured, please try again.');
        const hasFieldSpecificErrors =  errors.length > 0;

        const newState:any = {
            editable: true,
            globalError: null,
            passwordErrors: [],
            usernameErrors: []
        };

        if (generalError || hasFieldSpecificErrors) {
            if (hasFieldSpecificErrors) { // Field Errors
                logError('SignInForm Errors', errors);
                errors.map((error, index) => {
                    error.timestamp = new Date().getTime(); // Timestamp to make sure state changes, even if the message is the same
                    const targetError = error.field + 'Errors';

                    if (newState[targetError]) {
                        newState[targetError] = [...newState[targetError], error];
                    } else {
                        newState[targetError] = [];
                        newState[targetError].push(error);
                    }
                });
            } else { // Global message
                newState.globalError = generalError;
            }
        } else { // Something went really wrong. Add default message to tell the user there's a problem.
            newState.globalError = catchAllErrorMessage;
        }
        this.setState(newState);
    }

    public handleSubmit = (event) => {
        event.preventDefault();

        this.setState({
            editable: false
        });

        apiv2.post('/authenticate/password', {
            'username': this.state.username,
            'password': this.state.password,
        }).then((r) => {
            window.location.href = get(this, 'props.location.query.target', '/');
        }).catch((e) => {
            this.handleErrors(e);
        });
    }

    public render() {
        if (this.state.redirectTo) {
            return <BrowserRouter>
                <Route path={this.state.redirectTo} component={SignInForm} />
            </BrowserRouter>;
        } else {
            return <form id={this.ID} className="signInForm" method="post" onSubmit={this.handleSubmit} noValidate>
                <Paragraph parentID={this.ID} className="authenticateUser-paragraph" content={this.state.globalError} isError={true} />
                <InputTextBlock
                    parentID={this.ID}
                    label={t('Email/Username')}
                    required={true}
                    disabled={!this.state.editable}
                    errors={this.state.usernameErrors}
                    value={this.state.username}
                    onChange={this.handleTextChange}
                />
                <PasswordTextBlock
                    parentID={this.ID}
                    label={t('Password')}
                    required={true}
                    disabled={!this.state.editable}
                    errors={this.state.passwordErrors}
                    value={this.state.password}
                    onChange={this.handleTextChange}
                />
                <ButtonSubmit parentID={this.ID} disabled={!this.state.editable || this.state.password.length === 0 || this.state.username.length === 0} content={t('Sign In')}/>
                <CreateAnAccountLink/>
            </form>;
        }
    }
}

export default withRouter(SignInForm);
