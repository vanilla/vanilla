/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";

import { t } from "@library/application";
import Heading from "@library/components/Heading";
import classNames from "classnames";
import { IHelpData } from "@knowledge/modules/navigation/NavigationSelector";
import NavLinks from "@library/components/NavLinks";
import ScreenReaderContent from "@library/components/ScreenReaderContent";

interface IProps {
    title: string; // For accessibility, title of group
    classNames?: string;
    linkGroups: IHelpData;
}

/**
 * Component for displaying lists in "tiles"
 */
export default class NavLinksWithHeadings extends Component<IProps> {
    public render() {
        const ungrouped = this.props.linkGroups.ungroupedArticles || [];
        const grouped = this.props.linkGroups.groups || [];

        if (ungrouped.length !== 0 || grouped.length !== 0) {
            const ungroupedContent = <NavLinks articles={ungrouped} title={t("Overview")} />;
            const groupedContent = grouped.map(group => {
                return <NavLinks articles={group.articles} title={group.category.name} />;
            });

            return (
                <nav className={classNames("navLinksWithHeadings", this.props.classNames)}>
                    <ScreenReaderContent>
                        <Heading title={this.props.title} />
                    </ScreenReaderContent>
                    {ungroupedContent}
                    {groupedContent}
                </nav>
            );
        } else {
            return null;
        }
    }
}
