import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import apiv2 from "@core/apiv2";
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    ssoMethods?: any[];
    passwordAuthenticator?: any;
    ID: string;
}

export default class SignInPage extends React.Component<{}, IState> {
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.state = {
            ID: uniqueIDFromPrefix("SignInPage"),
            loginFormActive: false,
            errors: [],
        };
    }

    get titleID():string {
        return this.state.ID + "-pageTitle";
    }

    public componentDidMount() {
        log('SignInPage - /authenticate/authenticators');
        apiv2.get('/authenticate/authenticators')
            .then((response) => {
                log('RecoverPasswordPage - authenticators response: ', response);
                if (response.data) {



                    // const externalMethods:any[] = [];
                    // response.data.map((method, index) => {
                    //     log('SignInForm method: ', method);
                    //     if (method.authenticatorID === 'password') {
                    //         this.setState({
                    //             passwordAuthenticator: method,
                    //             loginFormActive: true,
                    //         });
                    //     } else {
                    //         externalMethods.push(method);
                    //     }
                    // });

                    const ssoMethods = response.data;
                    delete ssoMethods.password; //remove default
                    this.setState({
                        ssoMethods: ssoMethods,
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
        return <div id={this.state.ID} className="authenticateUserCol">
            <DocumentTitle id={this.titleID} classNames="isCentered" title={t('Sign In')}/>
            <SSOMethods parentID={this.state.ID} ssoMethods={this.state.ssoMethods} />
            <SignInForm parentID={this.state.ID}/>
        </div>;
    }
}
