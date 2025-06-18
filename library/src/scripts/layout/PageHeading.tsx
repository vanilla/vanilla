/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ConditionalWrap from "@library/layout/ConditionalWrap";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import Heading from "@library/layout/Heading";
import { pageHeadingClasses } from "@library/layout/pageHeadingStyles";
import BackLink from "@library/routing/links/BackLink";
import classNames from "classnames";
import React from "react";

interface IPageHeading {
    title?: React.ReactNode;
    depth?: number;
    renderDepth?: number;
    children?: React.ReactNode;
    className?: string;
    headingClassName?: string;
    actions?: React.ReactNode;
    includeBackLink?: boolean;
    titleCount?: React.ReactNode;
}

/**
 * A component representing a top level page heading.
 * Can be configured with an options menu and a backlink.
 */
export function PageHeading(props: IPageHeading) {
    const { includeBackLink = true, actions, children, headingClassName, title, className, titleCount } = props;

    const classes = pageHeadingClasses.useAsHook();
    const device = useDevice();
    const isMobile = [Devices.MOBILE, Devices.XS].includes(device);

    return (
        <div className={classNames(classes.root, className)}>
            <div className={classes.main}>
                {includeBackLink && !isMobile && <BackLink />}
                <ConditionalWrap condition={!!actions} className={classes.titleWrap}>
                    <Heading
                        isLarge={props.depth === 1}
                        renderAsDepth={props.renderDepth}
                        depth={props.depth}
                        title={title}
                        className={headingClassName}
                    >
                        {children}
                    </Heading>
                </ConditionalWrap>
            </div>
            {(actions || titleCount) && (
                <div className={classes.actions}>
                    {titleCount}
                    {actions}
                </div>
            )}
        </div>
    );
}

export default PageHeading;
