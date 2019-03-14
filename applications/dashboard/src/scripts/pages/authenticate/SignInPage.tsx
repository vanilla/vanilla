/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { connect } from "react-redux";
import { t } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import { getRequiredID, IRequiredComponentID } from "@library/utility/idUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import Or from "@dashboard/components/forms/Or";
import { IStoreState, IAuthenticatorState } from "@dashboard/@types/state";
import { getUserAuthenticators } from "@dashboard/pages/authenticate/authenticatorsActions";
import SSOMethods from "@dashboard/pages/authenticate/components/SSOMethods";
import PasswordForm from "@dashboard/pages/authenticate/components/PasswordForm";

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
            // TODO: Use a generic fallback component for the other states.
            return null;
        }

        let showPassword = false;
        const ssoMethods = authenticatorState.data!.filter(a => {
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

function mapStateToProps({ authenticate }: IStoreState) {
    return {
        authenticatorState: authenticate.authenticators,
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
