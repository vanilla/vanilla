/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getFieldErrors, getGlobalErrorMessage } from "@library/apiv2";
import { LoadStatus } from "@library/@types/api/core";
import { t } from "@library/utility/appUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import React from "react";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import Paragraph from "@library/layout/Paragraph";
import InputTextBlock from "@library/forms/InputTextBlock";
import uniqueId from "lodash/uniqueId";
import { IStoreState, IRequestPasswordState } from "@dashboard/@types/state";
import { connect } from "react-redux";
import {
    postRequestPassword,
    afterRequestPasswordSuccessNavigate,
} from "@dashboard/pages/recoverPassword/recoverPasswordActions";
import RememberPasswordLink from "@dashboard/pages/authenticate/components/RememberPasswordLink";
import { IRequestPasswordOptions } from "@dashboard/@types/api/authenticate";

interface IState {
    email: string;
}

interface IProps {
    requestPasswordState: IRequestPasswordState;
    postRequestPassword: typeof postRequestPassword;
    onNavigateAway: () => void;
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
                    <Paragraph className="authenticateUser-paragraph">
                        {t("A message has been sent to your email address with password reset instructions.")}
                    </Paragraph>
                    <RememberPasswordLink onClick={this.props.onNavigateAway} />
                </div>
            );
        } else {
            return (
                <div className="authenticateUserCol">
                    {pageTitle}
                    <Paragraph className="authenticateUser-paragraph">
                        {t("RecoverPasswordLabelCode", "Enter your email to continue.")}
                    </Paragraph>
                    <form id={this.id} onSubmit={this.handleSubmit} aria-labelledby={this.pageTitleID} noValidate>
                        <Paragraph className="authenticateUser-paragraph" isError={true}>
                            {getGlobalErrorMessage(this.props.requestPasswordState.error, ["email"])}
                        </Paragraph>
                        <InputTextBlock
                            label={t("Email")}
                            inputProps={{
                                required: true,
                                value: this.state.email,
                                onChange: this.handleEmailChange,
                                disabled: !this.allowEdit,
                            }}
                            errors={getFieldErrors(this.props.requestPasswordState.error, "email")}
                            ref={this.emainInput}
                        />
                        <ButtonSubmit disabled={!this.allowEdit || this.state.email.length === 0} legacyMode={true}>
                            {t("Request a new password")}
                        </ButtonSubmit>
                    </form>
                    <RememberPasswordLink />
                </div>
            );
        }
    }

    /**
     * Whenever the component gets new form state, we check for errors and focus the relavent errored inputs.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.requestPasswordState === prevProps.requestPasswordState) {
            return;
        }
        if (this.props.requestPasswordState.status === LoadStatus.ERROR) {
            this.emainInput.current!.select();
        }
    }

    /**
     * Change handler for the email input.
     */
    private handleEmailChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({ email: value });
    };

    /**
     * Submit handler for the form.
     */
    private handleSubmit = event => {
        event.preventDefault();
        const { email } = this.state;
        this.props.postRequestPassword({ email });
    };

    /**
     * Whether or not the user can edit elements in the form.
     */
    private get allowEdit() {
        return this.props.requestPasswordState.status !== LoadStatus.LOADING;
    }
}

function mapStateToProps({ authenticate }: IStoreState) {
    return {
        requestPasswordState: authenticate.password,
    };
}

function mapDispatchToProps(dispatch) {
    return {
        postRequestPassword: (params: IRequestPasswordOptions) => {
            dispatch(postRequestPassword(params));
        },
        onNavigateAway: () => {
            dispatch(afterRequestPasswordSuccessNavigate());
        },
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(RecoverPasswordPage);
