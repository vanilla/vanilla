/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import { chevronLeft } from "./Icons";
import classNames from "classnames";
import * as icons from "@dashboard/app/authenticate/components/Icons";

interface IProps {
    onClick: any;
    classNames?: string;
    iconClasses?: string;
    url?: string;
}

export default class BackLink extends React.Component<IProps> {
    public static defaultProps = {
        url: "#",
    };

    public render() {
        const buttonClasses = classNames("uiButton", "backLink", this.props.classNames);
        return (
            <a className={buttonClasses} onClick={this.props.onClick}>
                {icons.chevronLeft(this.props.iconClasses)}
                <span className="backLink-label">{t("Back")}</span>
            </a>
        );
    }
}
