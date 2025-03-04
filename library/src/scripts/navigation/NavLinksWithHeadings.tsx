/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

import { t } from "@library/utility/appUtils";
import { navLinksClasses, navLinksVariables } from "@library/navigation/navLinksStyles";
import { ILinkListData } from "@library/@types/api/core";
import NavLinks, { INavLinkNoItemComponent } from "@library/navigation/NavLinks";
import { cx } from "@emotion/css";

interface IProps {
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    data: ILinkListData;
    accessibleViewAllMessage?: string;
    ungroupedViewAllUrl?: string;
    ungroupedTitle?: string;
    NoItemsComponent?: INavLinkNoItemComponent;
}

/**
 * Component for displaying lists in "tiles"
 */
export default function NavLinksWithHeadings(props: IProps) {
    const {
        data,
        depth,
        ungroupedTitle = t("Overview"),
        accessibleViewAllMessage,
        ungroupedViewAllUrl,
        NoItemsComponent,
    } = props;

    const ungrouped = data.ungroupedItems || [];
    const grouped = data.groups || [];
    const groupLevel = Math.min((depth || 2) + 1, 6);
    const classes = navLinksClasses();
    const columns = navLinksVariables().columns.desktop;

    if (ungrouped.length > 0 || grouped.length > 0) {
        const ungroupedContent =
            ungrouped.length > 0 ? (
                <NavLinks
                    title={ungroupedTitle}
                    items={ungrouped}
                    accessibleViewAllMessage={accessibleViewAllMessage}
                    url={ungroupedViewAllUrl}
                />
            ) : null;
        const groupedContent = grouped.map((group, i) => {
            return (
                <React.Fragment key={i}>
                    <NavLinks
                        recordID={group.category.recordID}
                        recordType={group.category.recordType}
                        NoItemsComponent={props.NoItemsComponent}
                        items={group.items}
                        title={group.category.name}
                        url={group.category.url}
                        depth={groupLevel as 1 | 2 | 3 | 4 | 5 | 6}
                        accessibleViewAllMessage={props.accessibleViewAllMessage}
                        classNames={grouped.length - 1 === i ? "isLast" : ""}
                    />

                    {columns === 2 && <Separator className={i % 2 === 0 ? classes.separatorOdd : ""} />}
                    {columns === 3 && <Separator className={(i + 1) % 3 !== 0 ? classes.separatorOdd : ""} />}
                </React.Fragment>
            );
        });

        return (
            <section className={classes.linksWithHeadings}>
                {groupedContent}
                {ungroupedContent}
            </section>
        );
    }
    return null;
}

function Separator(props: { className?: string }) {
    return <hr className={cx(navLinksClasses().separator, props.className)} aria-hidden={true} />;
}
