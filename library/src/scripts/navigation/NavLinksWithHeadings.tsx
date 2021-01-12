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
import { navLinksClasses, navLinksVariables } from "@library/navigation/navLinksStyles";
import { ILinkListData } from "@library/@types/api/core";
import NavLinks, { INavLinkNoItemComponent } from "@library/navigation/NavLinks";
import Container from "@library/layout/components/Container";
import { visibility } from "@library/styles/styleHelpers";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";

interface IProps {
    title: string; // For accessibility, title of group
    showTitle?: boolean;
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    classNames?: string;
    data: ILinkListData;
    accessibleViewAllMessage?: string;
    ungroupedViewAllUrl?: string;
    ungroupedTitle?: string;
    NoItemsComponent?: INavLinkNoItemComponent;
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
        const columns = navLinksVariables().columns.desktop;

        if (ungrouped.length > 0 || grouped.length > 0) {
            const ungroupedContent =
                ungrouped.length > 0 ? (
                    <NavLinks
                        title={ungroupedTitle}
                        items={ungrouped}
                        accessibleViewAllMessage={this.props.accessibleViewAllMessage}
                        url={this.props.ungroupedViewAllUrl}
                    />
                ) : null;
            const groupedContent = grouped.map((group, i) => {
                return (
                    <React.Fragment key={i}>
                        <NavLinks
                            recordID={group.category.recordID}
                            recordType={group.category.recordType}
                            NoItemsComponent={this.props.NoItemsComponent}
                            items={group.items}
                            title={group.category.name}
                            url={group.category.url}
                            depth={groupLevel as 1 | 2 | 3 | 4 | 5 | 6}
                            accessibleViewAllMessage={this.props.accessibleViewAllMessage}
                            classNames={grouped.length - 1 === i ? "isLast" : ""}
                        />

                        {columns === 2 && this.separator(i % 2 === 0 ? classes.separatorOdd : "")}
                        {columns === 3 && this.separator((i + 1) % 3 !== 0 ? classes.separatorOdd : "")}
                    </React.Fragment>
                );
            });

            const sectionTitleID = uniqueIDFromPrefix("navLinksSectionTitle");

            return (
                <Container fullGutter narrow>
                    <section
                        aria-labelledby={sectionTitleID}
                        className={classNames("navLinksWithHeadings", this.props.classNames, classes.linksWithHeadings)}
                    >
                        <Heading
                            id={sectionTitleID}
                            title={this.props.title}
                            depth={this.props.depth}
                            className={classNames(
                                classes.title,
                                !this.props.showTitle && visibility().visuallyHidden,
                                this.props.showTitle && classes.topTitle,
                            )}
                        />
                        {groupedContent}
                        {ungroupedContent}
                    </section>
                </Container>
            );
        } else {
            return null;
        }
    }

    private separator(classes?: string) {
        return <hr className={classNames(navLinksClasses().separator, classes)} aria-hidden={true} />;
    }
}
