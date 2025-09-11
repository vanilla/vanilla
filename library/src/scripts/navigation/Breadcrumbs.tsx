/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import Breadcrumb from "@library/navigation/Breadcrumb";
import classNames from "classnames";
import { breadcrumbsClasses } from "@library/navigation/breadcrumbsStyles";
import { css } from "@emotion/css";

export interface ICrumb {
    name: string;
    url: string;
}

export interface IProps {
    children: ICrumb[];
    className?: string;
    forceDisplay?: boolean;
    minimumCrumbCount?: number;
}

/**
 * A component representing a string of breadcrumbs. Passa n arrow crumb props as children.
 */
export default function Breadcrumbs(props: IProps) {
    const minimumCrumbCount = props.minimumCrumbCount || 1;
    const crumbCount = props.children.length;
    const classes = breadcrumbsClasses.useAsHook();
    if (crumbCount < minimumCrumbCount && !props.forceDisplay) {
        return null;
    }

    let content: React.ReactNode;

    content = props.children.map((crumb, index) => {
        const lastElement = index === crumbCount - 1;
        const crumbSeparator = t("Breadcrumbs Crumb", { optional: true, fallback: `›` });
        return (
            <React.Fragment key={`breadcrumb-${index}`}>
                <Breadcrumb lastElement={lastElement} name={crumb.name} url={crumb.url} />
                {!lastElement && (
                    <li aria-hidden={true} className={classNames(classes.separator)} role="separator">
                        <span className={classes.separatorIcon}>{crumbSeparator}</span>
                    </li>
                )}
            </React.Fragment>
        );
    });

    const hasForcedCrumb = crumbCount === 0 && props.forceDisplay;
    if (hasForcedCrumb) {
        const cssClass = css({
            minHeight: 22,
            display: "inline-block",
        });
        content = <span className={cssClass} />;
    }

    return (
        <nav
            aria-label={t("Breadcrumb")}
            className={classNames(classes.root, props.className)}
            aria-hidden={hasForcedCrumb}
        >
            <ol className={classes.list}>{content}</ol>
        </nav>
    );
}
