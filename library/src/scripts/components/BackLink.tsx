/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
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
}

export class BackLink extends React.Component<IProps> {
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
                        onClick={this.clickHandler}
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
