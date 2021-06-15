/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { useWidgetLayoutClasses } from "@library/layout/WidgetLayout.context";
import { quickLinksClasses } from "@library/navigation/QuickLinks.classes";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import SmartLink from "@library/routing/links/SmartLink";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React from "react";

interface IProps {
    title?: string;
    links: Array<INavigationVariableItem & { count?: number; countLimit?: number | null }>;
}

/**
 * Component for displaying data lists
 */
export function QuickLinksView(props: IProps) {
    const classes = quickLinksClasses();
    const variables = quickLinksVariables();
    const { title, links } = props;
    const visibleLinks = links.filter((link) => {
        const isSetHidden = "isHidden" in link && link.isHidden;
        return !isSetHidden;
    });

    const widgetClasses = useWidgetLayoutClasses();

    return (
        <div className={cx(classes.root, widgetClasses.widgetClass)}>
            <PageHeadingBox title={title} />
            <PageBox options={variables.box}>
                <nav>
                    <ul className={classNames(classes.list, "no-css")}>
                        {visibleLinks ? (
                            visibleLinks.map((link) => (
                                <QuickLink
                                    key={link.id}
                                    path={link.url}
                                    title={link.name}
                                    count={link.count}
                                    countLimit={link.countLimit}
                                />
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
}

function QuickLink(props: IQuickLinkProps) {
    const classes = quickLinksClasses();
    const { path, title, count, countLimit, isHidden } = props;
    const displayCount = React.useMemo(() => {
        if (count && countLimit && count >= countLimit) {
            return `${countLimit}+`;
        }
        return count;
    }, [count, countLimit]);

    return (
        <li className={classNames(classes.listItem)}>
            <SmartLink className={classNames(classes.link)} to={path}>
                {t(title)}
            </SmartLink>
            {displayCount != null && <span className={classNames(classes.count)}>{displayCount}</span>}
        </li>
    );
}
