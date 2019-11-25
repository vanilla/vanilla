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
// export class PageHeading extends React.Component<IPageHeading> {

export function PageHeading(props: IPageHeading) {
    const { includeBackLink = true, actions, children, headingClassName, title, className } = props;

    // public context!: React.ContextType<typeof LineHeightCalculatorContext>;
    // public titleRef: React.RefObject<HTMLHeadingElement>;
    const ref = useRef<HTMLHeadingElement>(null);
    const { fontSize } = useFontSizeCalculator();

    const classes = pageHeadingClasses();
    const linkClasses = backLinkClasses();

    return (
        <div className={classNames(classes.root, className)}>
            <div className={classes.main}>
                {includeBackLink && <BackLink className={linkClasses.inHeading(fontSize)} />}
                <ConditionalWrap condition={!!actions} className={classes.titleWrap}>
                    <Heading titleRef={ref} depth={1} title={title} className={headingClassName}>
                        {children}
                    </Heading>
                </ConditionalWrap>
            </div>
            {actions && <div className={classes.actions(fontSize)}>{actions}</div>}
        </div>
    );
}
