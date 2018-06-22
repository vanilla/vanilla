/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import get from "lodash/get";
import classNames from "classnames";

interface IProps {
    id: string;
    error: string;
    className: string;
    linkOnClick: any;
    linkText: string;
    signInWithField: string;
}

export default class ErrorOrLinkLabel extends React.Component<IProps> {
    public render() {
        if (this.props.error) {
            const componentClasses = classNames("errorOrLinkLabel", this.props.className);

            return (
                <div id={this.props.id} className={componentClasses}>
                    <span className="errorOrLinkLabel-message">{this.props.error}</span>
                    <span className="errorOrLinkLabel-separator">{t("or")}</span>
                    <a href="#" tabIndex={0} onClick={this.props.linkOnClick} className="errorOrLinkLabel-link">
                        {this.props.linkText}
                    </a>
                </div>
            );
        } else {
            return null;
        }
    }
}
