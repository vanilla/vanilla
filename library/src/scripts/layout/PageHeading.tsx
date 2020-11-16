/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createRef, useContext, useEffect, useRef } from "react";
import classNames from "classnames";
import BackLink from "@library/routing/links/BackLink";
import Heading from "@library/layout/Heading";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { pageHeadingClasses } from "@library/layout/pageHeadingStyles";
import { IWithFontSize, useFontSizeCalculator } from "@library/layout/pageHeadingContext";
import backLinkClasses from "@library/routing/links/backLinkStyles";
import { iconClasses } from "@library/icons/iconStyles";

interface IPageHeading {
    title: React.ReactNode;
    children?: React.ReactNode;
    className?: string;
    headingClassName?: string;
    actions?: React.ReactNode;
    includeBackLink?: boolean;
    isCompactHeading?: boolean;
}

/**
 * A component representing a top level page heading.
 * Can be configured with an options menu and a backlink.
 */
// export class PageHeading extends React.Component<IPageHeading> {

export function PageHeading(props: IPageHeading) {
    const { includeBackLink = true, actions, children, headingClassName, title, className, isCompactHeading } = props;
    const { fontSize } = useFontSizeCalculator();

    const classes = pageHeadingClasses();
    const linkClasses = backLinkClasses();

    const backLink = isCompactHeading ? (
        <BackLink
            className={classNames(linkClasses.inHeading(fontSize), classes)}
            chevronClass={iconClasses().chevronLeftSmallCompact}
        />
    ) : (
        <BackLink className={classNames(linkClasses.inHeading(fontSize), classes)} />
    );

    return (
        <div className={classNames(classes.root, className)}>
            <div className={classes.main}>
                {includeBackLink && backLink}
                <ConditionalWrap condition={!!actions} className={classes.titleWrap}>
                    <Heading depth={1} title={title} className={headingClassName}>
                        {children}
                    </Heading>
                </ConditionalWrap>
            </div>
            {actions && <div className={classes.actions(fontSize)}>{actions}</div>}
        </div>
    );
}

export default PageHeading;
