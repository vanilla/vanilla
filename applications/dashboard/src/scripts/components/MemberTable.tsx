/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import { useLayout } from "@library/layout/LayoutContext";
import { memberListClasses } from "@dashboard/components/MemberList.styles";

interface IProps {
    children?: React.ReactNode;
}

/**
 * Component for displaying data lists
 * Because of accessibility concerns, the markup is a table not a data list.
 */
export function MemberTable(props: IProps) {
    const { children } = props;
    const { isCompact } = useLayout();
    const classes = memberListClasses();

    return (
        <table className={classes.table}>
            <caption>{t("Search Results")}</caption>
            <thead>
                <tr>
                    <th className={classNames(classes.head, classes.leftAlign, classes.isLeft, classes.mainColumn)}>
                        {t("User")}
                    </th>
                    {!isCompact && <th className={classNames(classes.head, classes.postsColumn)}>{t("Posts")}</th>}
                    {!isCompact && <th className={classNames(classes.head)}>{t("Registered")}</th>}
                    <th className={classNames(classes.head, classes.isRight, classes.lastActiveColumn)}>
                        {t("Last Active")}
                    </th>
                </tr>
            </thead>
            <tbody>{children}</tbody>
        </table>
    );
}
