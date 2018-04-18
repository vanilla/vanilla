import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import PasswordForm from "./Components/PasswordForm";
import {uniqueIDFromPrefix, getOptionalID, IOptionalComponentID} from '@core/Interfaces/componentIDs';

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    passwordAuthenticator?: any;
    genericError?: string;
    id: string;
}

export default class SignInPage extends React.Component<{}, IState> {
    public static defaultProps = {
        id: false,
    };

    constructor(props) {
        super(props);
        this.state = {
            id: uniqueIDFromPrefix("PasswordPage"),
            loginFormActive: false,
            errors: [],
        };
    }

    get titleID(): string {
        return this.state.id + "-pageTitle";
    }

    public render() {
        return <div className="authenticateUserCol">
            <DocumentTitle title={t('Sign In')}>
                <h1 id={this.titleID} className="isCentered">{t('Sign In')}</h1>
            </DocumentTitle>
            <PasswordForm/>
        </div>;
    }
}
