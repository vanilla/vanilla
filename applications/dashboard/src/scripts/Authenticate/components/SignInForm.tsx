import apiv2 from "@core/apiv2";
import { formatUrl, t } from '@core/application';
import React from 'react';
import { withRouter, BrowserRouter, Route } from 'react-router-dom';
import Paragraph from '../../Forms/Paragraph';
import InputTextBlock from "../../Forms/InputTextBlock";
import PasswordTextBlock from "../../Forms/PasswordTextBlock";
import ButtonSubmit from "../../Forms/ButtonSubmit";
import {getUniqueID} from '@core/Interfaces/componentIDs';

interface IProps {
    editable?: boolean;
    errors?: string[];
    ssoMethods?: any[];
}

interface IState {
    editable: boolean;
    errors?: string[];
    redirectTo?: string;
    active: boolean;
    passwordAuthenticator?: any[];
}

class SignInForm extends React.Component<IProps, IState> {
    public passwordAuthenticator: any[];
    public ID: string;


    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, 'signInForm');
        this.state = {
            editable: props.editable || true,
            errors: props.errors || [],
            active: false
        };

        if (props.ssoMethods) {
            props.ssoMethods.map((method, index) => {
                window.console.log("SignInForm method: ", method);
                // if matches passwordAuthenticator, set as passwordAuthenticator
            });
        }
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
        if (this.state.redirectTo) {
            return <BrowserRouter>
                <Route path={this.state.redirectTo} component={SignInForm} />
            </BrowserRouter>;
        } else {
            return <form className="signInForm">

                <InputTextBlock
                    parentID={this.ID}
                    label={t('Email/Username')}
                    placeholder={t('Enter Username')}
                    required={true}
                    errors={this.state.errors}
                />
                <PasswordTextBlock parentID={this.ID} label={t('Password')} placeholder={t('Enter Password')} required={true} errors={this.state.errors}/>
                <ButtonSubmit parentID={this.ID} content={t('Sign In')}/>
            </form>;
        }
    }
}

export default withRouter(SignInForm);
