/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { leftChevron } from "@library/components/Icons";
import { Link, RouteComponentProps, withRouter } from "react-router-dom";

interface IProps extends RouteComponentProps<{}> {
    url?: string | null;
    title?: string;
    className?: string;
    visibleLabel?: boolean;
    clickHandler?: () => void;
}

/**
 * A link button for navigating backwards. Uses a back arrow icon.
 */
export class BackLink extends React.Component<IProps> {
    public static defaultProps = {
        title: t("Back"),
        visibleLabel: false,
    };
    public render() {
        if (this.props.url) {
            return (
                <div className={classNames("backLink", this.props.className)}>
                    <Link
                        to={this.props.url}
                        aria-label={this.props.title}
                        title={this.props.title}
                        onClick={this.clickHandler}
                        className={classNames("backLink-link", { hasVisibleLabel: this.props.visibleLabel })}
                    >
                        {leftChevron("backLink-icon")}
                        {this.props.visibleLabel && <span className="backLink-label">{this.props.title}</span>}
                    </Link>
                </div>
            );
        } else {
            return null;
        }
    }

    /**
     * If we can do an actual back action on the history object we should.
     * Otherwise fallback to the default behaviour.
     */
    private clickHandler = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        this.props.history.goBack();
    };
}

export default withRouter(BackLink);
