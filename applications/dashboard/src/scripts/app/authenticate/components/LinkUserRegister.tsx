/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import { withRouter, BrowserRouter, Route, Link } from "react-router-dom";
import Paragraph from "@dashboard/components/forms/Paragraph";
import {log} from "@dashboard/utility";

export default class SsoUser extends React.Component<{}, {}> {
    public render() {
        log(t("SsoUser props: "), this.props);

        return (
            <div className="linkUserPassword">
                {t("Link user register")}
            </div>
        );
    }
}
