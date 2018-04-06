import apiv2 from "@core/apiv2";
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import * as PropTypes from "prop-types";
import { Link } from 'react-router-dom';
import React from 'react';
import UniqueID from "react-html-id";
import RememberPassword from "./RememberPassword";
import ButtonSubmit from "../Forms/ButtonSubmit";
import Paragraph from "../Forms/Paragraph";
import InputTextBlock from "../Forms/InputTextBlock";

interface IState {
    isEditable: boolean;
    emailSent: boolean;
    errors?: string[];
}

export default class RequestPasswordPage extends React.Component<IState> {
    public ID: string;
    public nextUniqueId: () => string;
    public parentID: string;

    constructor(props) {
        super(props);
        if (!props.ID) {
            UniqueID.enableUniqueIds(this);
            this.ID = 'RequestPasswordPage-' + this.nextUniqueId();
        } else {
            this.ID = props.ID;
        }

        this.state = {
            isEditable: true,
            emailSent: false,
        };

    }

    // Disable button when in submit state
    // Error handling from server side messages
    // If errors is empty, use global message, if not ignore and use per input messages

    public handleSubmit() {
        // this.setState({status: submitting});
        //
        // apiv2.post({
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

    public render() {
        const pageTitle = <DocumentTitle classNames="isCentered" title={t('Recover Password')}/>;
        if (this.state.emailSent) {
            return <div className="authenticateUserCol">
                {pageTitle}
                <Paragraph content={t('A message has been sent to your email address with password reset instructions.')} className="authenticateUser-paragraph" />
                <RememberPassword/>
            </div>;
        } else {
            return <div className="authenticateUserCol">
                {pageTitle}
                <Paragraph content={t('Enter your email address or username to continue.')} className="authenticateUser-paragraph" />
                <form onSubmit={this.handleSubmit}>
                    <InputTextBlock parentID={this.ID} label={t('Email/Username')} required={true} errors={this.state.errors}/>
                    <ButtonSubmit parentID={this.ID} content={t('Request a new password')}/>
                </form>
                <RememberPassword/>
            </div>;
        }
    }
}
