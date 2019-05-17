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
import backLinkClasses from "@library/routing/links/backLinkStyles";

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
        const linkClasses = backLinkClasses();
        const space = ` `;
        return (
            <div className={classNames(classes.root, this.props.className)}>
                <div className={classes.main}>
                    <div className={classes.titleWrap}>
                        {/*{this.props.includeBackLink && (*/}
                        {/*    <BackLink className={linkClasses.forHeading} fallbackElement={null}>*/}
                        {/*        <Heading*/}
                        {/*            depth={1}*/}
                        {/*            aria-hidden={true}*/}
                        {/*            className={classNames(this.props.headingClassName, linkClasses.getLineHeight)}*/}
                        {/*        >*/}
                        {/*            {space}*/}
                        {/*        </Heading>*/}
                        {/*    </BackLink>*/}
                        {/*)}*/}
                        <Heading depth={1} title={this.props.title} className={this.props.headingClassName}>
                            {this.props.children}
                        </Heading>
                    </div>
                </div>
                {/*{this.props.actions && <div className={classes.actions}>{this.props.actions}</div>}*/}
            </div>
        );
    }
}
