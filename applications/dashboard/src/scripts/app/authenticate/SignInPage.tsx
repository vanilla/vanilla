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
import PageLoading from "@dashboard/components/PageLoading";
import { IUserAuthenticator, LoadStatus } from "@dashboard/@types/api";
import { IStoreState, IAuthenticatorState } from "@dashboard/@types/state";
import { getUserAuthenticators } from "@dashboard/state/session/authenticatorsActions";

interface IProps {
    authenticatorState: IAuthenticatorState;
    loadAuthenticators: typeof getUserAuthenticators;
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
        if (this.props.authenticatorState.status === LoadStatus.PENDING) {
            this.props.loadAuthenticators();
        }
    }

    public render() {
        const { authenticatorState } = this.props;

        if (authenticatorState.status !== LoadStatus.SUCCESS) {
            return (
                <div id={this.state.id} className="authenticateUserCol">
                    <PageLoading {...authenticatorState} />
                </div>
            );
        }

        let showPassword = false;
        const ssoMethods = authenticatorState.data.filter(a => {
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

function mapStateToProps({ session }: IStoreState) {
    return {
        authenticatorState: session.authenticators,
    };
}

function mapDispatchToProps(dispatch) {
    return {
        loadAuthenticators: () => {
            dispatch(getUserAuthenticators());
        },
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(SignInPage);
