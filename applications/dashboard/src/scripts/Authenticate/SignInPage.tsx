import * as React from "react";
import { connect } from "react-redux";
import { t } from "@core/application";
import DocumentTitle from "@core/Components/DocumentTitle";
import { authenticatorsGet } from "./state/actions";
import PasswordForm from "./Components/PasswordForm";
import SSOMethods from "./Components/SSOMethods";
import { getRequiredID, IRequiredComponentID } from "@core/Interfaces/componentIDs";
import Or from "../Forms/Or";

interface IProps {
    ssoMethods: object[];
    dispatchAuthenticatorsGet: () => void;
}

interface IState extends IRequiredComponentID {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    passwordAuthenticator?: any;
}

class SignInPage extends React.Component<IProps, IState> {
    public pageTitleID: string;

    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "SignInPage"),
            loginFormActive: false,
            errors: [],
        };
    }

    get titleID(): string {
        return this.state.id + "-pageTitle";
    }

    public componentDidMount() {
        this.props.dispatchAuthenticatorsGet();
    }

    public render() {
        const or = this.props.ssoMethods.length > 0 ? <Or /> : null;
        return (
            <div id={this.state.id} className="authenticateUserCol">
                <DocumentTitle title={t("Sign In")}>
                    <h1 id={this.titleID} className="isCentered">
                        {t("Sign In")}
                    </h1>
                </DocumentTitle>
                <SSOMethods ssoMethods={this.props.ssoMethods} />
                {or}
                <PasswordForm />
            </div>
        );
    }
}

/* Map one or more pieces of the Redux state tree to props in this component. */
const mapState = state => ({
    ssoMethods: state.authenticate.authenticators.filter(authenticator => authenticator.authenticatorID !== "password"),
});

/* Map one or more action dispatchers to the props in this component. */
const mapActions = dispatch => ({
    dispatchAuthenticatorsGet: () => dispatch(authenticatorsGet()),
});

export default connect(mapState, mapActions)(SignInPage);
