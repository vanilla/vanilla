import apiv2 from "@core/apiv2";
import { formatUrl, t } from '@core/application';
import React from 'react';
import { withRouter, BrowserRouter, Route, Link } from 'react-router-dom';
import { log, logError, debug } from "@core/utility";
import InputTextBlock from "../../Forms/InputTextBlock";
import Checkbox from "../../Forms/Checkbox";
import PasswordTextBlock from "../../Forms/PasswordTextBlock";
import ButtonSubmit from "../../Forms/ButtonSubmit";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import Paragraph from "../../Forms/Paragraph";
import get from "lodash/get";
import RememberPasswordLink from "./RememberPasswordLink";

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
    rememberMe: boolean;
}

class SignInForm extends React.Component<IProps, IState> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueIDFromPrefix('signInForm');
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleTextChange = this.handleTextChange.bind(this);
        this.handleCheckBoxChange = this.handleCheckBoxChange.bind(this);
        this.handleErrors = this.handleErrors.bind(this);

        this.state = {
            editable: true,
            username: '',
            password: '',
            redirectTo: null,
            submitEnabled: false,
            rememberMe: true,
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

    public handleCheckBoxChange = (event) => {
        const value:boolean = get(event, 'target.checked', false);
        this.setState({
            rememberMe: value
        });
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
            'persist': this.state.rememberMe,
        }).then((r) => {
            const search = get(this, 'props.location.search', '/');
            const params = new URLSearchParams(search);
            window.location.href = formatUrl(params.get('target') || '/');
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
                <div className="inputBlock inputBlock-tighter">
                    <div className="rememberMeAndForgot">
                        <span className="rememberMeAndForgot-rememberMe">
                            <Checkbox parentID={this.ID} label={t('Keep me signed in')} onChange={this.handleCheckBoxChange} checked={this.state.rememberMe}/>
                        </span>
                        <span className="rememberMeAndForgot-forgot">
                            <Link to="/authenticate/recoverpassword">{t('Forgot password?')}</Link>
                        </span>
                    </div>
                </div>
                <ButtonSubmit parentID={this.ID} disabled={!this.state.editable || this.state.password.length === 0 || this.state.username.length === 0} content={t('Sign In')}/>
            </form>;
        }
    }
}

export default withRouter(SignInForm);
