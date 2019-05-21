/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import BackLink from "@library/routing/links/BackLink";
import Heading from "@library/layout/Heading";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { pageHeadingClasses } from "@library/layout/pageHeadingStyles";

interface IPageHeading {
    title: string;
    children?: React.ReactNode;
    className?: string;
    headingClassName?: string;
    actions?: React.ReactNode;
    includeBackLink?: boolean;
}

/**
 * A component representing a top level page heading.
 * Can be configured with an options menu and a backlink.
 */
export default class PageHeading extends React.Component<IPageHeading> {
    public static defaultProps = {
        includeBackLink: true,
    };
    public render() {
        const classes = pageHeadingClasses();
        return (
            <div className={classNames(classes.root, this.props.className)}>
                <div className={classes.main}>
                    {this.props.includeBackLink && <BackLink fallbackElement={null} />}
                    <ConditionalWrap condition={!!this.props.actions} className={classes.titleWrap}>
                        <Heading depth={1} title={this.props.title} className={this.props.headingClassName}>
                            {this.props.children}
                        </Heading>
                    </ConditionalWrap>
                </div>
                {this.props.actions && <div className={classes.actions}>{this.props.actions}</div>}
            </div>
        );
    }
}
