/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { formatUrl, t } from "@dashboard/application";
import React from "react";
import { withRouter, BrowserRouter, Route, Link } from "react-router-dom";
import Paragraph from "@dashboard/components/forms/Paragraph";
import { log, logError } from "@dashboard/utility";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import apiv2 from "@dashboard/apiv2";
import { getRequiredID, IRequiredComponentID } from "@dashboard/componentIDs";
import get from "lodash/get";
import ButtonSubmit from "@dashboard/components/forms/ButtonSubmit";
import Checkbox from "@dashboard/components/forms/Checkbox";
import classNames from "classnames";

interface IProps {
    id: string;
    errors: string | string[];
    className: string;
    linkOnClick: any;
    linkText: string;
}

interface IState {
    errorMessage: string;
}

export default class ErrorOrLinkLabel extends React.Component<IProps, IState> {
    public render() {
        const componentClasses = classNames("errorOrLinkLabel", this.props.className);

        let message;
        if (!(this.props.errors instanceof Array)) {
            message = [this.props.errors];
        }

        message = (get(this, "props.errors", []) as string[]).join(". ").trim();

        return (
            <div id={this.props.id} className={componentClasses}>
                <span className="errorOrLinkLabel-message">{message}</span>
                <span className="errorOrLinkLabel-separator">{t("or")}</span>
                <a href="#" onClick={this.props.linkOnClick} className="errorOrLinkLabel-link">
                    {this.props.linkText}
                </a>
            </div>
        );
    }
}
