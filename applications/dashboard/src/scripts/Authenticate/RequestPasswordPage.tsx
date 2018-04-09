import apiv2 from "@core/apiv2";
import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import * as PropTypes from "prop-types";
import { Link } from 'react-router-dom';
import React from 'react';
import { getUniqueID, IComponentID } from '@core/Interfaces/componentIDs';
import ButtonSubmit from "../Forms/ButtonSubmit";
import Paragraph from "../Forms/Paragraph";
import InputTextBlock from "../Forms/InputTextBlock";
import RememberPasswordLink from "./components/RememberPasswordLink";

interface IProps extends IComponentID{
    isEditable: boolean;
    emailSent: boolean;
    errors?: string[];
}

interface IState {
    isEditable: boolean;
    emailSent: boolean;
    errors?: string[];
}

export default class RequestPasswordPage extends React.Component<IState, IProps> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, 'RequestPasswordPage');

        this.state = {
            isEditable: props.isEditable || true,
            emailSent: props.emailSent || false,
            errors: props.errors || [],
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
        const pageTitle = <DocumentTitle parentID={this.ID} className="isCentered" title={t('Recover Password')}/>;
        if (this.state.emailSent) {
            return <div className="authenticateUserCol">
                {pageTitle}
                <Paragraph content={t('A message has been sent to your email address with password reset instructions.')} className="authenticateUser-paragraph" />
                <RememberPasswordLink/>
            </div>;
        } else {
            return <div className="authenticateUserCol">
                {pageTitle}
                <Paragraph content={t('Enter your email address or username to continue.')} className="authenticateUser-paragraph" />
                <form id={this.ID} onSubmit={this.handleSubmit} aria-labelledby={t('')}>
                    <InputTextBlock parentID={this.ID} label={t('Email/Username')} required={true} errors={this.state.errors}/>
                    <ButtonSubmit parentID={this.ID} content={t('Request a new password')}/>
                </form>
                <RememberPasswordLink/>
            </div>;
        }
    }
}
