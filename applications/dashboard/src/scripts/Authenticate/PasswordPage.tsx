import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import { uniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import apiv2 from "@core/apiv2";
import Paragraph from "../Forms/Paragraph";

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    passwordAuthenticator?: any;
    genericError?: string;
}

export default class SignInPage extends React.Component<{}, IState> {
    public ID: string;
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueIDFromPrefix('PasswordPage');
        this.pageTitleID = this.ID + '-pageTitle';
        this.state = {
            loginFormActive: false,
            errors: [],
        };
    }

    public render() {
        return <div id={this.ID} className="authenticateUserCol">
            <DocumentTitle id={this.pageTitleID} classNames="isCentered" title={t('Sign In')}/>
            <SignInForm parentID={this.ID}/>
        </div>;
    }
}
