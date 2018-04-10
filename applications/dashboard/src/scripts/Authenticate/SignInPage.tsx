import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import apiv2 from "@core/apiv2";

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    ssoMethods?: any[];
    passwordAuthenticator?: any;
}

export default class SignInPage extends React.Component<{}, IState> {
    public ID: string;
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueIDFromPrefix('SignInPage');
        this.pageTitleID = this.ID + '-pageTitle';
        this.state = {
            loginFormActive: false,
            errors: [],
        };
    }

    public componentDidMount() {
        log('SignInPage - /authenticate/authenticators');
        apiv2.get('/authenticate/authenticators')
            .then((response) => {
                log('RecoverPasswordPage - authenticators response: ', response);
                if (response.data) {
                    const externalMethods:any[] = [];
                    response.data.map((method, index) => {
                        log('SignInForm method: ', method);
                        if (method.authenticatorID === 'password') {
                            this.setState({
                                passwordAuthenticator: method,
                                loginFormActive: true,
                            });
                        } else {
                            externalMethods.push(method);
                        }
                    });
                    this.setState({
                        ssoMethods: externalMethods,
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
        const pageTitle = <DocumentTitle id={this.pageTitleID} classNames="isCentered" title={t('Sign In')}/>;
        return <div id={this.ID} className="authenticateUserCol">
            {pageTitle}
            <SSOMethods parentID={this.ID} ssoMethods={this.state.ssoMethods} />
            <SignInForm parentID={this.ID} passwordAuthenticator={this.state.passwordAuthenticator}/>
        </div>;
    }
}
