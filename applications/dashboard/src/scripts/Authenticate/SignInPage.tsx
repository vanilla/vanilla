import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import SSOMethods from "./components/SSOMethods";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import apiv2 from "@core/apiv2";
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';
import Or from "../Forms/Or";

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    ssoMethods: any[];
    passwordAuthenticator?: any;
    id: string;
}

export default class SignInPage extends React.Component<{}, IState> {
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.state = {
            id: uniqueIDFromPrefix("SignInPage"),
            loginFormActive: false,
            errors: [],
            ssoMethods: [],
        };
    }

    get titleID():string {
        return this.state.id + "-pageTitle";
    }

    public componentDidMount() {
        apiv2.get('/authenticate/authenticators')
            .then((response) => {
                log('SignIn Page - authenticators response: ', response);
                if (response.data) {
                    response.data.map((method, index) => {
                        if (method.authenticatorID != 'password') {
                            this.setState(prevState => ({
                                ssoMethods: [...prevState.ssoMethods as any[], method],
                            }));
                        }
                    });
                } else {
                    logError('Error in RecoverPasswordPage - no response.data');
                }
            }).catch((error) => {
                logError('Error in RecoverPasswordPage - authenticators response: ', error);
            }
        );
    }


    public render() {
        const or = this.state.ssoMethods.length > 0 ? <Or/> : null;
        return <div id={this.state.id} className="authenticateUserCol">
            <DocumentTitle id={this.titleID} classNames="isCentered" title={t('Sign In')}/>
            <SSOMethods parentID={this.state.id} ssoMethods={this.state.ssoMethods} />
            {or}
            <SignInForm parentID={this.state.id}/>
        </div>;
    }
}
