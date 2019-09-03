/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";

import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";
import classNames from "classnames";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { navLinksClasses } from "@library/navigation/navLinksStyles";
import { ILinkListData } from "@library/@types/api/core";
import NavLinks from "@library/navigation/NavLinks";

interface IProps {
    title: string; // For accessibility, title of group
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    classNames?: string;
    data: ILinkListData;
    accessibleViewAllMessage?: string;
    ungroupedViewAllUrl?: string;
    ungroupedTitle?: string;
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
        const ungroupedTitle = this.props.ungroupedTitle || t("Overview");

        window.console.log("this.props", this.props);

        if (ungrouped.length !== 0 || grouped.length !== 0) {
            const ungroupedContent = (
                <NavLinks
                    title={ungroupedTitle}
                    items={ungrouped}
                    accessibleViewAllMessage={this.props.accessibleViewAllMessage}
                    url={this.props.ungroupedViewAllUrl}
                />
            );
            const groupedContent = grouped.map((group, i) => {
                return (
                    <React.Fragment key={i}>
                        <NavLinks
                            items={group.items}
                            title={group.category.name}
                            url={group.category.url}
                            depth={groupLevel as 1 | 2 | 3 | 4 | 5 | 6}
                            accessibleViewAllMessage={this.props.accessibleViewAllMessage}
                            classNames={grouped.length - 1 === i ? "isLast" : ""}
                        />
                        {this.separator(i % 2 === 0 ? classes.separatorOdd : "")}
                    </React.Fragment>
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

    private separator(classes?: string) {
        return (
            <hr className={classNames(navLinksClasses().separator, classes)} aria-hidden={true} role="presentation" />
        );
    }
}
