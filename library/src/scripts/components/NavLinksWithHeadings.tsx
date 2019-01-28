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

interface IProps {
    title: string; // For accessibility, title of group
    classNames?: string;
    data: ILinkListData;
}

/**
 * Component for displaying lists in "tiles"
 */
export default class NavLinksWithHeadings extends Component<IProps> {
    public render() {
        const ungrouped = this.props.data.ungroupedItems || [];
        const grouped = this.props.data.groups || [];

        if (ungrouped.length !== 0 || grouped.length !== 0) {
            const ungroupedContent = <NavLinks title={t("Overview")} items={ungrouped} />;
            const groupedContent = grouped.map(group => {
                return <NavLinks items={group.items} title={group.category.name} />;
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
