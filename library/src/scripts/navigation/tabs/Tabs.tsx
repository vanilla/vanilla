/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import TabPanel, { ITabPanel } from "@library/navigation/tabs/TabPanel";
import TabButtonList, { ITabButton } from "@library/navigation/tabs/TabButtonList";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";

export interface ITab extends ITabPanel, ITabButton {}

interface IProps {
    className?: string;
    label: string;
    buttonClass?: string;
    tabListClass?: string;
    tabPanelsClass?: string;
    tabPanelClass?: string;
    tabs: ITab[];
    extraTabContent?: React.ReactNode;
}

interface IState {
    selectedTab: number;
}

/**
 * Clean up conditional renders with this component
 */
export default class Tabs extends React.PureComponent<IProps, IState> {
    private id = uniqueIDFromPrefix("tabs");
    public constructor(props) {
        super(props);
        this.state = {
            selectedTab: 0,
        };
    }

    public render() {
        const { tabs, className } = this.props;
        return (
            <>
                <TabButtonList
                    tabs={this.props.tabs}
                    selectedTab={this.state.selectedTab}
                    setTab={this.setSelectedTab}
                    getTabButtonID={this.tabButtonID}
                    getTabPanelID={this.tabPanelId}
                    label={this.props.label}
                    parentID={this.id}
                    buttonClass={this.props.buttonClass}
                    className={this.props.tabListClass}
                    extraContent={this.props.extraTabContent}
                />
                <TabPanel
                    tabs={this.props.tabs}
                    selectedTab={this.state.selectedTab}
                    getTabButtonID={this.tabButtonID}
                    getTabPanelID={this.tabPanelId}
                    parentID={this.id}
                    tabPanelClass={this.props.tabPanelClass}
                    className={this.props.tabPanelsClass}
                />
            </>
        );
    }

    private setSelectedTab = (selectedTab: number) => {
        this.setState({
            selectedTab,
        });
    };

    private tabButtonID = (index: number) => {
        return `${this.id}-tabButton-${index}`;
    };

    private tabPanelId = (index: number) => {
        return `${this.id}-tabPanel-${index}`;
    };
}
