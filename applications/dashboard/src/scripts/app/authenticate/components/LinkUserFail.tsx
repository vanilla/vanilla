/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import { withRouter, BrowserRouter, Route, Link } from "react-router-dom";
import Paragraph from "@dashboard/components/forms/Paragraph";

interface IProps {
    message?: string;
}

export default class LinkUserFail extends React.Component<IProps, {}> {
    public render() {
        const message = this.props.message ? this.props.message : t("There was an error!");
        return (
            <div className="authenticateUserCol">
                <Paragraph className="authenticateUser-paragraph" content={message} />
                <Link className="button Button Primary buttonCTA BigButton button-fullWidth" to="/authenticate/signin">
                    {t("Try again")}
                </Link>
            </div>
        );
    }
}
