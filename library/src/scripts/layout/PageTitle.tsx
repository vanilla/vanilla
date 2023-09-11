/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import * as React from "react";
import { PageHeading } from "@library/layout/PageHeading";
import { useSection } from "@library/layout/LayoutContext";
import { Metas } from "@library/metas/Metas";

export interface IPageTitle {
    title: React.ReactNode;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    className?: string;
    includeBackLink?: boolean;
    headingClassName?: string;
    isLarge?: boolean;
    children?: React.ReactNode;
}

/**
 * Generates main title for page as well as possibly a back link and some meta information about the page
 */
export function PageTitle(props: IPageTitle) {
    const { title, children, actions, meta, className, includeBackLink = true, headingClassName } = props;

    const isCompact = useSection().isCompact;

    return (
        <div className={cx("pageTitleContainer", className)}>
            <PageHeading
                actions={actions}
                title={title}
                depth={1}
                includeBackLink={!isCompact && includeBackLink}
                headingClassName={headingClassName}
            >
                {children}
            </PageHeading>
            {meta && <Metas>{meta}</Metas>}
        </div>
    );
}
