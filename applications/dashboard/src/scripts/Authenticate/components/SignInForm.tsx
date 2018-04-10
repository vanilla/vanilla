import apiv2 from "@core/apiv2";
import { formatUrl, t } from '@core/application';
import React from 'react';
import { withRouter, BrowserRouter, Route } from 'react-router-dom';
import Paragraph from '../../Forms/Paragraph';
import InputTextBlock from "../../Forms/InputTextBlock";
import PasswordTextBlock from "../../Forms/PasswordTextBlock";
import ButtonSubmit from "../../Forms/ButtonSubmit";
import {getUniqueID} from '@core/Interfaces/componentIDs';
import CreateAnAccountLink from "./CreateAnAccountLink";

interface IProps {
    editable?: boolean;
    errors?: string[];
    passwordAuthenticator?: any;
}

interface IState {
    editable: boolean;
    errors?: string[];
    redirectTo?: string;
    active: boolean;
}

class SignInForm extends React.Component<IProps, IState> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, 'signInForm');
        this.state = {
            editable: props.editable || true,
            errors: props.errors || [],
            active: false
        };
    }

    // // Disable button when in submit state
    // // Error handling from server side messages
    // // If errors is empty, use global message, if not ignore and use per input messages
    //
    public handleSubmit() {
    //     this.setState({status: submitting});
    //
    //     apiv2.post({
    //         username: this.username,
    //         password: this.password,
    //         persist: this.persist,
    //     }).then((r) => {
    //         // Do the redirect.
    //         let target = this.props.location.query.target || '/';
    //         window.location.href = formats
    //     }).catch((e) => {
    //         this.setState({
    //             status: undefined,
    //             errors: normalizeErorrs(response.data.errors),
    //         });
    //     };
    }
    public componentDidMount() {

    }


    public render() {
        if (this.props.passwordAuthenticator) {
            if (this.state.redirectTo) {
                return <BrowserRouter>
                    <Route path={this.state.redirectTo} component={SignInForm} />
                </BrowserRouter>;
            } else {
                window.console.log("passwordAuthenticator: ", this.props.passwordAuthenticator);
                return <form className="signInForm" method="post" action={this.props.passwordAuthenticator.signInUrl}>
                    <InputTextBlock
                        parentID={this.ID}
                        label={t('Email/Username')}
                        placeholder={t('Enter Username')}
                        required={true}
                        disabled={!this.state.editable}
                        errors={this.state.errors}
                    />
                    <PasswordTextBlock parentID={this.ID} disabled={!this.state.editable} label={t('Password')} placeholder={t('Enter Password')} required={true} errors={this.state.errors}/>
                    <ButtonSubmit parentID={this.ID} disabled={!this.state.editable} content={t('Sign In')}/>
                    <CreateAnAccountLink link={this.props.passwordAuthenticator.registerUrl}/>
                </form>;
            }
        } else {
            return null;
        }
    }
}

export default withRouter(SignInForm);
