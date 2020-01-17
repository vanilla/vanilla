/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadingRectange } from "@library/loaders/LoadingRectangle";
import { breadcrumbsClasses } from "@library/navigation/breadcrumbsStyles";
import { t } from "@library/utility/appUtils";
import React from "react";

export interface IProps {
    crumbCount?: number;
}

/**
 * A component representing a string of breadcrumbs. Passa n arrow crumb props as children.
 */
export default function BreadcrumbsLoader(props: IProps) {
    const classes = breadcrumbsClasses();
    const crumbCount = props.crumbCount ?? 2;

    return (
        <nav aria-label={t("Breadcrumb")} className={classes.root}>
            <ol className={classes.list}>
                {Array.from(Array(crumbCount)).map((_, index) => {
                    const lastElement = index === crumbCount - 1;
                    const crumbSeparator = ` `;
                    return (
                        <React.Fragment key={index}>
                            <LoadingCrumb />
                            {!lastElement && (
                                <li aria-hidden={true} className={classes.separator}>
                                    <span className={classes.separatorIcon}>{crumbSeparator}</span>
                                </li>
                            )}
                        </React.Fragment>
                    );
                })}
            </ol>
        </nav>
    );
}

function LoadingCrumb() {
    const classes = breadcrumbsClasses();
    return (
        <li className={classes.breadcrumb}>
            <span className={classes.link}>
                <LoadingRectange height={12} width={100} />
            </span>
        </li>
    );
}
