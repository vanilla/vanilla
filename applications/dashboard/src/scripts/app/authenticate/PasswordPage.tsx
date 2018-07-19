/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { t } from "@dashboard/application";
import DocumentTitle from "@dashboard/components/DocumentTitle";
import PasswordForm from "./components/PasswordForm";
import { uniqueIDFromPrefix } from "@dashboard/componentIDs";

export default class SignInPage extends React.Component {
    private id = uniqueIDFromPrefix("PasswordPage");

    get titleID(): string {
        return this.id + "-pageTitle";
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
