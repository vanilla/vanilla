/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { connect } from "react-redux";
import { t } from "@dashboard/application";
import DocumentTitle from "@dashboard/components/DocumentTitle";
import PasswordForm from "./components/PasswordForm";
import SSOMethods from "./components/SSOMethods";
import { getRequiredID, IRequiredComponentID } from "@dashboard/componentIDs";
import Or from "@dashboard/components/forms/Or";
import { ISigninAuthenticatorState } from "@dashboard/state/authenticate/IAuthenticateState";
import { Dispatch } from "redux";
import { getSigninAuthenticators } from "@dashboard/state/authenticate/authenticatorsActions";
import PageLoading from "@dashboard/components/PageLoading";
import IState from "@dashboard/state/IState";
import { LoadStatus } from "@dashboard/apiv2";

interface IProps {
    authenticators: ISigninAuthenticatorState;
    loadAuthenticators: typeof getSigninAuthenticators;
}

export class SignInPage extends React.Component<IProps, IRequiredComponentID> {
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "SignInPage"),
        };
    }

    get titleID(): string {
        return this.state.id + "-pageTitle";
    }

    public componentDidMount() {
        if (this.props.authenticators.status === LoadStatus.PENDING) {
            this.props.loadAuthenticators();
        }
    }

    public render() {
        const { authenticators } = this.props;

        if (authenticators.status !== LoadStatus.SUCCESS) {
            return (
                <div id={this.state.id} className="authenticateUserCol">
                    <PageLoading {...authenticators} />
                </div>
            );
        }

        let showPassword = false;
        const ssoMethods = authenticators.data.filter(a => {
            if (a.type === "password") {
                showPassword = true;
                return false;
            }
            return true;
        });

        return (
            <div id={this.state.id} className="authenticateUserCol">
                <DocumentTitle title={t("Sign In")}>
                    <h1 id={this.titleID} className="isCentered">
                        {t("Sign In")}
                    </h1>
                </DocumentTitle>
                <SSOMethods ssoMethods={ssoMethods} />
                <Or visible={showPassword && ssoMethods.length > 0} />
                {showPassword && <PasswordForm />}
            </div>
        );
    }
}

function mapStateToProps({ authenticate }: IState) {
    return {
        authenticators: authenticate.signin,
    };
}

function mapDispatchToProps(dispatch) {
    return {
        loadAuthenticators: () => {
            dispatch(getSigninAuthenticators());
        },
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(SignInPage);
