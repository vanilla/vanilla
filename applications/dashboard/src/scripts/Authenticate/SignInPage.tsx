import * as React from "react";
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import SignInForm from "./components/SignInForm";
import CreateAnAccountLink from "./components/CreateAnAccountLink";
import SSOMethods from "./components/SSOMethods";
import UniqueID from "react-html-id";

export default class PasswordPage extends React.Component {
    public ID: string;
    public nextUniqueId: () => string;
    public parentID: string;


    constructor(props) {
        super(props);
        if (!props.ID) {
            UniqueID.enableUniqueIds(this);
            this.ID = 'SignInPage-' + this.nextUniqueId();
        } else {
            this.ID = props.ID;
        }

        this.state = {
            isEditable: props.isEditable || true,
            errors: props.errors || [],
        };
    }


    public render() {
        const pageTitle = <DocumentTitle classNames="isCentered" title={t('Sign In')}/>;
        return <div className="authenticateUserCol">
            {pageTitle}
            <SSOMethods />
            <SignInForm/>
            <CreateAnAccountLink/>
        </div>;
    }
}
