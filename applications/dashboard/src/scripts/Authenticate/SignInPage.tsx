import * as React from "react";
import { t } from '@core/application';
import { log, logError, debug } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import { getUniqueIDFromPrefix } from '@core/Interfaces/componentIDs';
import apiv2 from "@core/apiv2";

interface IState {
    editable: boolean;
    errors?: string[];
    redirectTo?: string;
    ssoMethods?: any[];
}

export default class SignInPage extends React.Component<{}, IState> {
    public ID: string;
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueIDFromPrefix('SignInPage');
        this.pageTitleID = this.ID + '-pageTitle';
        this.state = {
            editable: false,
            errors: [],
        };
    }

    public componentDidMount() {
        log('SignInPage - /authenticate/authenticators');
        apiv2.get('/authenticate/authenticators')
            .then((response) => {
                log('RecoverPasswordPage - authenticators response: ', response);
                if (response.statusText === "OK") {
                    this.setState({
                        ssoMethods: response.data,
                        editable: true,
                    });
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
            <SignInForm parentID={this.ID} ssoMethods={this.state.ssoMethods}/>
            <CreateAnAccountLink/>
        </div>;
    }
}
