/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { getFieldErrors, getGlobalErrorMessage } from "@dashboard/apiv2";
import { t } from "@dashboard/application";
import DocumentTitle from "@dashboard/components/DocumentTitle";
import React from "react";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Paragraph from "@dashboard/components/forms/Paragraph";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import RememberPasswordLink from "./components/RememberPasswordLink";
import uniqueId from "lodash/uniqueId";
import { IStoreState, IRequestPasswordState } from "@dashboard/@types/state";
import { IRequestPasswordOptions, LoadStatus } from "@dashboard/@types/api";
import { postRequestPassword } from "@dashboard/state/users/requestPasswordActions";
import { connect } from "react-redux";

interface IState {
    email: string;
}

interface IProps {
    requestPasswordState: IRequestPasswordState;
    requestPassword: typeof postRequestPassword;
}

export class RecoverPasswordPage extends React.Component<IProps, IState> {
    public id = uniqueId("RecoverPasswordPage");
    public pageTitleID: string;
    public emainInput: React.RefObject<InputTextBlock> = React.createRef();

    constructor(props) {
        super(props);
        this.id = this.pageTitleID = this.id + "-pageTitle";

        this.state = {
            email: "",
        };
    }

    public render() {
        const pageTitle = (
            <DocumentTitle title={t("Recover Password")}>
                <h1 id={this.pageTitleID} className="isCentered">
                    {t("Recover Password")}
                </h1>
            </DocumentTitle>
        );

        if (this.props.requestPasswordState.status === LoadStatus.SUCCESS) {
            return (
                <div id={this.id} className="authenticateUserCol">
                    {pageTitle}
                    <Paragraph
                        content={t("A message has been sent to your email address with password reset instructions.")}
                        className="authenticateUser-paragraph"
                    />
                    <RememberPasswordLink />
                </div>
            );
        } else {
            return (
                <div className="authenticateUserCol">
                    {pageTitle}
                    <Paragraph
                        content={t("RecoverPasswordLabelCode", "Enter your email to continue.")}
                        className="authenticateUser-paragraph"
                    />
                    <form id={this.id} onSubmit={this.handleSubmit} aria-labelledby={this.pageTitleID} noValidate>
                        <Paragraph
                            className="authenticateUser-paragraph"
                            content={getGlobalErrorMessage(this.props.requestPasswordState, ["email"])}
                            isError={true}
                        />
                        <InputTextBlock
                            label={t("Email/Username")}
                            required={true}
                            disabled={!this.allowEdit}
                            errors={getFieldErrors(this.props.requestPasswordState, "email")}
                            value={this.state.email}
                            onChange={this.handleEmailChange}
                            ref={this.emainInput}
                        />
                        <ButtonSubmit
                            disabled={!this.allowEdit || this.state.email.length === 0}
                            content={t("Request a new password")}
                        />
                    </form>
                    <RememberPasswordLink />
                </div>
            );
        }
    }

    private handleEmailChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const value = event.target.value;
        this.setState({ email: value });
    };

    private handleSubmit = event => {
        event.preventDefault();
        const { email } = this.state;
        this.props.requestPassword({ email });
    };

    private get allowEdit() {
        return this.props.requestPasswordState.status !== LoadStatus.LOADING;
    }
}

function mapStateToProps({ users }: IStoreState) {
    return {
        requestPasswordState: users.requestPassword,
    };
}

function mapDispatchToProps(dispatch) {
    return {
        requestPassword: (params: IRequestPasswordOptions) => {
            dispatch(postRequestPassword(params));
        },
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);
export default withRedux(RecoverPasswordPage);
