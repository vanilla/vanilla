/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import Permission from "@library/features/users/Permission";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { useWidgetSectionClasses } from "@library/layout/WidgetLayout.context";
import { quickLinksClasses } from "@library/navigation/QuickLinks.classes";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React from "react";

interface IProps {
    title?: string;
    links: Array<INavigationVariableItem & { count?: number; countLimit?: number | null }>;
    activePath?: string;
}

/**
 * Component for displaying data lists
 */
export function QuickLinksView(props: IProps) {
    const classes = quickLinksClasses();
    const variables = quickLinksVariables();
    const { title, links, activePath } = props;
    const visibleLinks = links.filter((link) => {
        const isSetHidden = "isHidden" in link && link.isHidden;
        return !isSetHidden;
    });

    const widgetClasses = useWidgetSectionClasses();

    return (
        <div className={cx(classes.root, widgetClasses.widgetClass)}>
            <PageHeadingBox title={title} />
            <PageBox options={variables.box}>
                <nav>
                    <ul className={classNames(classes.list, "no-css")}>
                        {visibleLinks ? (
                            visibleLinks.map((link, index) => (
                                <Permission key={index} permission={link.permission}>
                                    <QuickLink
                                        active={link.url === activePath}
                                        path={link.url}
                                        title={link.name}
                                        count={link.count}
                                        countLimit={link.countLimit}
                                    />
                                </Permission>
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
    countLimit?: number | null;
    isHidden?: boolean;
    active?: boolean;
}

function QuickLink(props: IQuickLinkProps) {
    const classes = quickLinksClasses();
    const { path, title, count, countLimit, active } = props;
    const displayCount = React.useMemo(() => {
        if (count && countLimit && count >= countLimit) {
            return `${countLimit}+`;
        }
        return count;
    }, [count, countLimit]);

    return (
        <li className={classNames(classes.listItem)}>
            <SmartLink to={path} active={active} className={classes.link(active)}>
                {t(title)}
                {displayCount != null && <span className={classNames(classes.count)}>{displayCount}</span>}
            </SmartLink>
        </li>
    );
}
