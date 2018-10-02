/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "../application";
import { Link } from "react-router-dom";
import { leftChevron } from "@library/components/Icons";

interface IProps {
    url?: string;
    title?: string;
    className?: string;
}

/**
 * A link button for navigating backwards. Uses a back arrow icon.
 */
export default class BackLink extends React.Component<IProps> {
    public static defaultProps = {
        title: t("Back"),
    };
    public render() {
        if (this.props.url) {
            return (
                <div className={classNames("backLink", this.props.className)}>
                    <Link
                        to={this.props.url}
                        aria-label={this.props.title}
                        title={this.props.title}
                        className="backLink-link"
                    >
                        {leftChevron("backLink-icon")}
                    </Link>
                </div>
            );
        } else {
            return null;
        }
    }
}
