/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { PageBox } from "@library/layout/PageBox";
import { quickLinksClasses } from "@library/navigation/QuickLinks.classes";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import SmartLink from "@library/routing/links/SmartLink";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React from "react";

interface IProps {
    title?: string;
    links: Array<INavigationVariableItem & { count?: number }>;
}

/**
 * Component for displaying data lists
 */
export function QuickLinksView(props: IProps) {
    const classes = quickLinksClasses();
    const variables = quickLinksVariables();
    const domID = useUniqueID("quickLinkTitle");
    const { title, links } = props;
    const visibleLinks = links.filter((link) => {
        const isSetHidden = "isHidden" in link && link.isHidden;
        return !isSetHidden;
    });

    return (
        <div className={classNames(classes.root)} aria-labelledby={domID}>
            <h2 className={classNames(classes.title, "no-css", variables.box)} id={domID}>
                {title}
            </h2>
            <PageBox options={variables.box}>
                <nav>
                    <ul className={classNames(classes.list, "no-css")}>
                        {visibleLinks ? (
                            visibleLinks.map((link) => (
                                <QuickLink key={link.id} path={link.url} title={link.name} count={link.count} />
                            ))
                        ) : (
                            <></>
                        )}
                    </ul>
                </nav>
            </PageBox>
        </div>
    );
}
interface IQuickLinkProps {
    path: string;
    title: string;
    count?: number;
    isHidden?: boolean;
}

function QuickLink(props: IQuickLinkProps) {
    const classes = quickLinksClasses();
    const { path, title, count, isHidden } = props;
    return (
        <li className={classNames(classes.listItem)}>
            <SmartLink className={classNames(classes.listItemTitle)} to={path}>
                {t(title)}
            </SmartLink>
            {count != null && <span className={classNames(classes.count)}>{count}</span>}
        </li>
    );
}
