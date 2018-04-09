import * as React from "react";
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import { getUniqueIDFromPrefix } from '@core/Interfaces/componentIDs';

export default class SignInPage extends React.Component {
    public ID: string;
    public pageTitleID: string;


    constructor(props) {
        super(props);

        this.ID = getUniqueIDFromPrefix('SignInPage');
        window.console.log("generated id: ", this.ID);
        this.pageTitleID = this.ID + '-pageTitle';
        this.state = {
            isEditable: props.isEditable || true,
            errors: props.errors || [],
        };
    }


    public render() {
        const pageTitle = <DocumentTitle id={this.pageTitleID} classNames="isCentered" title={t('Sign In')}/>;
        return <div id={this.ID} className="authenticateUserCol">
            {pageTitle}
            <SSOMethods parentID={this.ID} />
            <SignInForm parentID={this.ID}/>
            <CreateAnAccountLink/>
        </div>;
    }
}
