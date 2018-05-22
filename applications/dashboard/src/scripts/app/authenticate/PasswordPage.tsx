/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { t } from "@dashboard/application";
import DocumentTitle from "@dashboard/components/DocumentTitle";
import PasswordForm from "./components/PasswordForm";
import { uniqueIDFromPrefix } from "@dashboard/componentIDs";

interface IState {
    loginFormActive: boolean;
    errors?: string[];
    redirectTo?: string;
    passwordAuthenticator?: any;
    genericError?: string;
    id: string;
}

export default class SignInPage extends React.Component<{}, IState> {
    public static defaultProps = {
        id: false,
    };

    constructor(props) {
        super(props);
        this.state = {
            id: uniqueIDFromPrefix("PasswordPage"),
            loginFormActive: false,
            errors: [],
        };
    }

    get titleID(): string {
        return this.state.id + "-pageTitle";
    }

    public render() {
        return (
            <div className="authenticateUserCol">
                <DocumentTitle title={t("Sign In")}>
                    <h1 id={this.titleID} className="isCentered">
                        {t("Sign In")}
                    </h1>
                </DocumentTitle>
                <PasswordForm />
            </div>
        );
    }
}
