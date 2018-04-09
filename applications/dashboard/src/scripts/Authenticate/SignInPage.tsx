import * as React from "react";
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import { getUniqueID } from '@core/Interfaces/componentIDs';

export default class PasswordPage extends React.Component {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, 'SignInPage');
        this.state = {
            isEditable: props.isEditable || true,
            errors: props.errors || [],
        };
    }


    public render() {
        const pageTitle = <DocumentTitle parentID={this.ID} classNames="isCentered" title={t('Sign In')}/>;
        return <div className="authenticateUserCol">
            {pageTitle}
            <SSOMethods parentID={this.ID} />
            <SignInForm parentID={this.ID}/>
            <CreateAnAccountLink/>
        </div>;
    }
}
