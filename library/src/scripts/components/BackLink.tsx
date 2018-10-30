/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t, formatUrl } from "@library/application";
import { leftChevron } from "@library/components/Icons";
import { Link, RouteComponentProps, withRouter } from "react-router-dom";

interface IProps extends RouteComponentProps<{}> {
    url?: string;
    title?: React.ReactNode;
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
        const routingUrl = this.props.url ? this.props.url : formatUrl("/kb");

        return (
            <div className={classNames("backLink", this.props.className)}>
                <Link
                    to={routingUrl}
                    aria-label={this.props.title as string}
                    title={this.props.title as string}
                    onClick={this.clickHandler}
                    className={classNames("backLink-link", { hasVisibleLabel: this.props.visibleLabel })}
                >
                    {leftChevron("backLink-icon")}
                    {this.props.visibleLabel && <span className="backLink-label">{this.props.title}</span>}
                </Link>
            </div>
        );
    }

    /**
     * If we can do an actual back action on the history object we should.
     * Otherwise fallback to the default behaviour.
     */
    private clickHandler = (event: React.MouseEvent) => {
        if (!this.props.url) {
            event.preventDefault();
            event.stopPropagation();
            this.props.history.goBack();
        }
    };
}

export default withRouter(BackLink);
