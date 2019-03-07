/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";

import { t } from "@library/application";
import Heading from "@library/components/Heading";
import classNames from "classnames";
import NavLinks from "@library/components/NavLinks";
import ScreenReaderContent from "@library/components/ScreenReaderContent";
import { ILinkListData } from "@library/@types/api";
import { navLinksClasses } from "@library/styles/navLinksStyles";

interface IProps {
    title: string; // For accessibility, title of group
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    classNames?: string;
    data: ILinkListData;
    accessibleViewAllMessage?: string;
}

/**
 * Component for displaying lists in "tiles"
 */
export default class NavLinksWithHeadings extends Component<IProps> {
    public render() {
        const ungrouped = this.props.data.ungroupedItems || [];
        const grouped = this.props.data.groups || [];
        const groupLevel = Math.min((this.props.depth || 2) + 1, 6);
        const classes = navLinksClasses();

        if (ungrouped.length !== 0 || grouped.length !== 0) {
            const ungroupedContent = <NavLinks title={t("Overview")} items={ungrouped} />;
            const groupedContent = grouped.map((group, i) => {
                return (
                    <NavLinks
                        items={group.items}
                        title={group.category.name}
                        url={group.category.url}
                        depth={groupLevel as 1 | 2 | 3 | 4 | 5 | 6}
                        accessibleViewAllMessage={this.props.accessibleViewAllMessage}
                        key={i}
                    />
                );
            });

            return (
                <nav className={classNames("navLinksWithHeadings", this.props.classNames, classes.linksWithHeadings)}>
                    <ScreenReaderContent>
                        <Heading title={this.props.title} depth={this.props.depth} />
                    </ScreenReaderContent>
                    {groupedContent}
                    {ungroupedContent}
                </nav>
            );
        } else {
            return null;
        }
    }
}
