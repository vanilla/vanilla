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
import { useLineHeightCalculator } from "@library/layout/pageHeadingContext";
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
    const { setLineHeight, lineHeight, offset } = useLineHeightCalculator();

    const classes = pageHeadingClasses();
    const linkClasses = backLinkClasses();

    useEffect(() => {
        if (ref.current) {
            const length = parseInt(getComputedStyle(ref.current)["line-height"], 10);
            const before = !!length && setLineHeight(length);
        }
    }, [ref.current, setLineHeight]);

    return (
        <div className={classNames(classes.root, className)}>
            <div className={classes.main}>
                {includeBackLink && <BackLink fallbackElement={null} className={linkClasses.inHeading(lineHeight)} />}
                <ConditionalWrap condition={!!actions} className={classes.titleWrap}>
                    <Heading titleRef={ref} depth={1} title={title} className={headingClassName}>
                        {children}
                    </Heading>
                </ConditionalWrap>
            </div>
            {actions && <div className={classes.actions(lineHeight)}>{actions}</div>}
        </div>
    );
}
