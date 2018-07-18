/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import apiv2 from "@dashboard/apiv2";
import { formatUrl, t } from "@dashboard/application";
import React from "react";
import { withRouter, Link } from "react-router-dom";
import { logError } from "@dashboard/utility";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import Checkbox from "@dashboard/components/forms/Checkbox";
import get from "lodash/get";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Paragraph from "@dashboard/components/forms/Paragraph";
import { IRequiredComponentID, getRequiredID } from "@dashboard/componentIDs";
import { IStoreState, IPasswordState } from "@dashboard/@types/state";
import { postAuthenticatePassword } from "@dashboard/state/authenticate/passwordActions";
import { IAuthenticatePasswordParams, LoadStatus, IFieldError } from "@dashboard/@types/api";
import { connect } from "react-redux";

interface IProps {
    password: string;
    username: string;
    passwordState: IPasswordState;
    authenticate: typeof postAuthenticatePassword;
}

interface IState extends IRequiredComponentID {
    allowSubmit: boolean;
    rememberMe: boolean;
}

class PasswordForm extends React.Component<IProps, IState> {
    private usernameInput: React.RefObject<InputTextBlock> = React.createRef();
    private passwordInput: React.RefObject<InputTextBlock> = React.createRef();

    constructor(props) {
        super(props);

        this.state = {
            id: getRequiredID(props, "passwordForm"),
            allowSubmit: false,
            rememberMe: true,
        };
    }

    public render() {
        let formDescribedBy;
        if (this.globalErrorMessage) {
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
                    content={this.globalErrorMessage}
                    isError={true}
                />
                <InputTextBlock
                    label={t("Email/Username")}
                    required={true}
                    disabled={!this.allowEdit}
                    errors={this.getFieldErrors("username")}
                    defaultValue={this.props.username}
                    ref={this.usernameInput}
                />
                <InputTextBlock
                    label={t("Password")}
                    required={true}
                    disabled={!this.allowEdit}
                    errors={this.getFieldErrors("password")}
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

    public componentDidUpdate(prevProps: IProps) {
        if (this.getFieldErrors("username")) {
            this.usernameInput.current!.select();
        } else if (this.getFieldErrors("password")) {
            this.passwordInput.current!.select();
        } else {
            this.usernameInput.current!.select();
        }
    }

    private getFieldErrors(field: string): IFieldError[] | undefined {
        const { passwordState } = this.props;
        if (
            passwordState.status === LoadStatus.ERROR &&
            passwordState.error.errors &&
            passwordState.error.errors[field]
        ) {
            return passwordState.error.errors[field];
        }
    }

    private get allowEdit() {
        return this.props.passwordState.status !== LoadStatus.LOADING;
    }

    /**
     * The global error message only should show if there are no field specific error messages.
     */
    private get globalErrorMessage(): string {
        const { passwordState } = this.props;
        if (passwordState.status !== LoadStatus.ERROR) {
            return "";
        }

        const fields = ["username", "password"];
        for (const field of fields) {
            if (this.getFieldErrors(field)) {
                return "";
            }
        }

        return passwordState.error.message || t("An error has occurred, please try again.");
    }

    private handleCheckBoxChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.setState({ rememberMe: event.target.checked || false });
    };

    // public setErrors(globalError, passwordErrors: string[], usernameErrors: string[]) {
    //     this.setState(
    //         {
    //             allowEdit: true,
    //             passwordErrors,
    //             usernameErrors,
    //             globalError,
    //         },
    //         () => {
    //             const hasGlobalError = !!this.state.globalError;
    //             const hasPasswordError = this.state.passwordErrors.length > 0;
    //             const hasUsernameError = this.state.usernameErrors.length > 0;

    //             if (hasGlobalError && !hasPasswordError && !hasUsernameError) {
    //                 this.usernameInput.select();
    //             } else if (hasUsernameError) {
    //                 this.usernameInput.select();
    //             } else if (hasPasswordError) {
    //                 this.passwordInput.select();
    //             }
    //         },
    //     );
    // }

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
