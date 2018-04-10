import apiv2 from "@core/apiv2";
import { t } from '@core/application';
import { log } from "@core/utility";
import DocumentTitle from '@core/Components/DocumentTitle';
import { Link } from 'react-router-dom';
import React from 'react';
import { uniqueIDFromPrefix, IComponentID } from '@core/Interfaces/componentIDs';
import ButtonSubmit from "../Forms/ButtonSubmit";
import Paragraph from "../Forms/Paragraph";
import InputTextBlock from "../Forms/InputTextBlock";
import RememberPasswordLink from "./components/RememberPasswordLink";

interface IProps extends IComponentID{
    editable: boolean;
    emailSent: boolean;
    errors?: string[];
}

interface IState {
    editable: boolean;
    submitting: boolean;
    emailSent: boolean;
    errors?: string[];
}

export default class RecoverPasswordPage extends React.Component<IProps, IState> {
    public ID: string;
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueIDFromPrefix('RecoverPasswordPage');
        this.pageTitleID = this.ID + '-pageTitle';

        this.state = {
            editable: props.editable || true,
            emailSent: props.emailSent || false,
            errors: props.errors || [],
            submitting: false,
        };
    }



    // Disable button when in submit state
    // Error handling from server side messages
    // If errors is empty, use global message, if not ignore and use per input messages

    public handleSubmit() {
        // this.setState({
        //     submitting: true,
        // });
        //
        // apiv2.post('/', {
        //     username: this.username,
        //     password: this.password,
        //     persist: this.persist,
        // }).then((r) => {
        //     // Do the redirect.
        //     let target = this.props.location.query.target || '/';
        //     window.location.href = formats
        // }).catch((e) => {
        //     this.setState({
        //         status: undefined,
        //         errors: normalizeErorrs(response.data.errors),
        //     });
        // };
    }


    public normalizeErorrs() {
        window.console.log("Handle errors");
    }

    public isDisabled() {
        return !this.state.editable && !this.state.submitting;
    }

    public render() {
        const pageTitle = <DocumentTitle id={this.pageTitleID} className="isCentered" title={t('Recover Password')}/>;
        if (this.state.emailSent) {
            return <div id={this.ID} className="authenticateUserCol">
                {pageTitle}
                <Paragraph content={t('A message has been sent to your email address with password reset instructions.')} className="authenticateUser-paragraph" />
                <RememberPasswordLink/>
            </div>;
        } else {
            return <div id={this.ID} className="authenticateUserCol">
                {pageTitle}
                <Paragraph content={t('Enter your email address or username to continue.')} className="authenticateUser-paragraph" />
                <form id={this.ID} onSubmit={this.handleSubmit} aria-labelledby={t('')}>
                    <InputTextBlock parentID={this.ID} disabled={this.isDisabled()} label={t('Email/Username')} required={true} errors={this.state.errors}/>
                    <ButtonSubmit parentID={this.ID} disabled={this.isDisabled()} content={t('Request a new password')}/>
                </form>
                <RememberPasswordLink/>
            </div>;
        }
    }
}
