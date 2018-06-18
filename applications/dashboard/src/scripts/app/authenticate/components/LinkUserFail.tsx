/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import { withRouter, BrowserRouter, Route, Link } from "react-router-dom";
import Paragraph from "@dashboard/components/forms/Paragraph";

export default class LinkUserFail extends React.Component<{}, {}> {
    public render() {
        return (
            <div className="authenticateUserCol">
                <Paragraph className="authenticateUser-paragraph" content={t("There was an error!")} />
                <Link to="/authenticate/signin">{t("Try again")}</Link>
            </div>
        );
    }
}
