/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import BackLink from "@library/components/navigation/BackLink";
import Heading from "@library/components/Heading";

interface IPageHeading {
    title: string;
    children?: React.ReactNode;
    className?: string;
    actions?: React.ReactNode;
    includeBackLink?: boolean;
}

/**
 * A component representing a top level page heading.
 * Can be configured with an options menu and a backlink.
 */
export default class PageHeading extends React.Component<IPageHeading> {
    public defaultProps = {
        includeBackLink: true,
    };
    public render() {
        return (
            <div className={classNames("pageHeading", this.props.className)}>
                <div className="pageHeading-main">
                    {this.props.includeBackLink && <BackLink className="pageHeading-backLink" fallbackElement={null} />}
                    <Heading depth={1} title={this.props.title}>
                        {this.props.children}
                    </Heading>
                </div>
                {this.props.actions && <div className="pageHeading-actions">{this.props.actions}</div>}
            </div>
        );
    }
}
