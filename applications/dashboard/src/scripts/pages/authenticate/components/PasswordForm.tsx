/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getFieldErrors, getGlobalErrorMessage } from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import React from "react";
import { withRouter, Link } from "react-router-dom";
import InputTextBlock from "@library/forms/InputTextBlock";
import Checkbox from "@library/forms/Checkbox";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import Paragraph from "@library/layout/Paragraph";
import { IRequiredComponentID, getRequiredID } from "@library/utility/idUtils";
import { IStoreState, IPasswordState } from "@dashboard/@types/state";
import { connect } from "react-redux";
import { LoadStatus } from "@library/@types/api/core";
import { postAuthenticatePassword } from "@dashboard/pages/authenticate/passwordActions";
import { IAuthenticatePasswordParams } from "@dashboard/@types/api/authenticate";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import classNames from "classnames";

interface IProps {
    passwordState: IPasswordState;
    authenticate: typeof postAuthenticatePassword;
    defaultUsername?: string;
}

interface IState extends IRequiredComponentID {
    rememberMe: boolean;
    username: string;
    password: string;
    id: string;
}

/**
 * Username + password from for signins.
 */
export class PasswordForm extends React.Component<IProps, IState> {
    private usernameInput: React.RefObject<InputTextBlock> = React.createRef();
    private passwordInput: React.RefObject<InputTextBlock> = React.createRef();

    constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "passwordForm"),
            rememberMe: true,
            username: "",
            password: "",
        };
    }

    public render() {
        let formDescribedBy;
        const globalErrorMessage = getGlobalErrorMessage(this.props.passwordState.error, ["username", "password"]);
        const classes = inputBlockClasses();
        if (globalErrorMessage) {
            formDescribedBy = this.formDescriptionID;
        }

        return (
            <form
                id={this.state.id}
                aria-describedby={formDescribedBy}
                className="passwordForm"
                method="post"
                onSubmit={this.handleSubmit}
                noValidate
            >
                <Paragraph id={this.formDescriptionID} className="authenticateUser-paragraph" isError={true}>
                    globalErrorMessage
                </Paragraph>
                <InputTextBlock
                    label={t("Email/Username")}
                    errors={getFieldErrors(this.props.passwordState.error, "username")}
                    ref={this.usernameInput}
                    inputProps={{
                        required: true,
                        disabled: !this.allowEdit,
                        onChange: this.handleUsernameChange,
                        defaultValue: this.props.defaultUsername || "",
                        value: this.state.username,
                    }}
                />
                <InputTextBlock
                    label={t("Password")}
                    ref={this.passwordInput}
                    errors={getFieldErrors(this.props.passwordState.error, "password")}
                    inputProps={{
                        required: true,
                        disabled: !this.allowEdit,
                        type: "password",
                        onChange: this.handlePasswordChange,
                        value: this.state.password,
                    }}
                />
                <div className={classNames(classes.root)}>
                    <div className="rememberMeAndForgot">
                        <span className="rememberMeAndForgot-rememberMe">
                            <Checkbox
                                label={t("Keep me signed in")}
                                onChange={this.handleCheckBoxChange}
                                checked={this.state.rememberMe}
                            />
                        </span>
                        <span className="rememberMeAndForgot-forgot">
                            <Link to="/authenticate/recoverpassword">{t("Forgot your password?")}</Link>
                        </span>
                    </div>
                </div>
                <ButtonSubmit disabled={!this.allowSubmit} legacyMode={true}>
                    {t("Sign In")}
                </ButtonSubmit>
            </form>
        );
    }

    /**
     * Whenever the component gets new form state, we check for errors and focus the relavent errored inputs.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.passwordState === prevProps.passwordState) {
            return;
        }

        if (getFieldErrors(this.props.passwordState.error, "username")) {
            this.usernameInput.current!.select();
        } else if (getFieldErrors(this.props.passwordState.error, "password")) {
            this.passwordInput.current!.select();
        } else {
            this.usernameInput.current!.select();
        }
    }

    /**
     * Whether or not inputs in the form can be edited.
     */
    private get allowEdit() {
        return this.props.passwordState.status !== LoadStatus.LOADING;
    }

    private get allowSubmit() {
        const { username, password } = this.state;
        return username.length > 0 && password.length > 0;
    }

    /**
     * Handler for the remember me checkbox.
     */
    private handleCheckBoxChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({ rememberMe: event.target.checked || false });
    };

    /**
     * Change handler for the email input.
     */
    private handleUsernameChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({ username: value });
    };

    /**
     * Change handler for the email input.
     */
    private handlePasswordChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({ password: value });
    };

    /**
     * Submit handler for the form.
     */
    private handleSubmit = (event: React.SyntheticEvent<any>) => {
        event.preventDefault();
        const { username, password, rememberMe } = this.state;

        this.props.authenticate({
            username,
            password,
            persist: rememberMe,
        });
    };

    /**
     * Get the description ID for the form.
     */
    private get formDescriptionID() {
        return this.state.id + "-description";
    }
}

function mapStateToProps({ authenticate }: IStoreState) {
    return {
        passwordState: authenticate.password,
    };
}

function mapDispatchToProps(dispatch) {
    return {
        authenticate: (params: IAuthenticatePasswordParams) => {
            dispatch(postAuthenticatePassword(params));
        },
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);
export default withRedux(withRouter(PasswordForm));
