/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import BackLink from "@library/components/BackLink";
import Heading from "@library/components/Heading";

interface IPageHeading {
    children: React.ReactNode;
    backUrl?: string | null; // back link
    className?: string;
    menu?: React.ReactNode;
}

/**
 * A component representing a top level page heading.
 * Can be configured with an options menu and a backlink.
 */
export default class PageHeading extends React.Component<IPageHeading> {
    public render() {
        return (
            <div className={classNames("pageHeading", this.props.className)}>
                <div className="pageHeading-main">
                    {this.props.backUrl === null ? null : (
                        <BackLink url={this.props.backUrl} className="pageHeading-backLink" />
                    )}
                    {/* Will not render if no url is passed */}
                    <Heading depth={1}>{this.props.children}</Heading>
                </div>
                {this.props.menu && <div className="pageHeading-actions">{this.props.menu}</div>}
            </div>
        );
    }
}
