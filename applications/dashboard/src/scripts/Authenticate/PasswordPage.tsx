import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    passwordAuthenticator?: any;
    genericError?: string;
    id: string;
}

export default class SignInPage extends React.Component<{}, IState> {
    constructor(props) {
        super(props);
        this.state = {
            id: uniqueIDFromPrefix("PasswordPage"),
            loginFormActive: false,
            errors: [],
        };
    }

    get titleID():string {
        return this.state.id + "-pageTitle";
    }

    public render() {
        return <div id={ this.state.id } className="authenticateUserCol">
            <DocumentTitle id={ this.titleID } classNames="isCentered" title={t('Sign In')}/>
            <SignInForm parentID={this.state.id}/>
        </div>;
    }
}
