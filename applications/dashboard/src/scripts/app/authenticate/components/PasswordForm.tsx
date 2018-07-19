/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { getFieldErrors, getGlobalErrorMessage } from "@dashboard/apiv2";
import { t } from "@dashboard/application";
import React from "react";
import { withRouter, Link } from "react-router-dom";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import Checkbox from "@dashboard/components/forms/Checkbox";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Paragraph from "@dashboard/components/forms/Paragraph";
import { IRequiredComponentID, getRequiredID } from "@dashboard/componentIDs";
import { IStoreState, IPasswordState } from "@dashboard/@types/state";
import { postAuthenticatePassword } from "@dashboard/state/authenticate/passwordActions";
import { IAuthenticatePasswordParams, LoadStatus } from "@dashboard/@types/api";
import { connect } from "react-redux";

interface IProps {
    password: string;
    username: string;
    passwordState: IPasswordState;
    authenticate: typeof postAuthenticatePassword;
}

interface IState extends IRequiredComponentID {
    rememberMe: boolean;
}

/**
 * Username + password from for signins.
 */
class PasswordForm extends React.Component<IProps, IState> {
    private usernameInput: React.RefObject<InputTextBlock> = React.createRef();
    private passwordInput: React.RefObject<InputTextBlock> = React.createRef();

    constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "passwordForm"),
            rememberMe: true,
        };
    }

    public render() {
        let formDescribedBy;
        const globalErrorMessage = getGlobalErrorMessage(this.props.passwordState, ["username", "password"]);
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
                <Paragraph
                    id={this.formDescriptionID}
                    className="authenticateUser-paragraph"
                    content={globalErrorMessage}
                    isError={true}
                />
                <InputTextBlock
                    label={t("Email/Username")}
                    required={true}
                    disabled={!this.allowEdit}
                    errors={getFieldErrors(this.props.passwordState, "username")}
                    defaultValue={this.props.username}
                    ref={this.usernameInput}
                />
                <InputTextBlock
                    label={t("Password")}
                    required={true}
                    disabled={!this.allowEdit}
                    errors={getFieldErrors(this.props.passwordState, "password")}
                    defaultValue={this.props.password}
                    type="password"
                    ref={this.passwordInput}
                />
                <div className="inputBlock inputBlock-tighter">
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
                <ButtonSubmit disabled={!this.allowEdit} content={t("Sign In")} />
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

        if (getFieldErrors(this.props.passwordState, "username")) {
            this.usernameInput.current!.select();
        } else if (getFieldErrors(this.props.passwordState, "password")) {
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

    /**
     * Handler for the remember me checkbox.
     */
    private handleCheckBoxChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({ rememberMe: event.target.checked || false });
    };

    /**
     * Submit handler for the form.
     */
    private handleSubmit = event => {
        event.preventDefault();
        if (!this.usernameInput.current || !this.passwordInput.current) {
            return;
        }

        this.props.authenticate({
            username: this.usernameInput.current.value,
            password: this.passwordInput.current.value,
            persist: this.state.rememberMe,
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
