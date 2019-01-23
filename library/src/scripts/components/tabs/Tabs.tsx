/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import TabPanel, { ITabPanel } from "@library/components/tabs/pieces/TabPanel";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import TabButtonList, { ITabButton } from "@library/components/tabs/pieces/TabButtonList";

export interface ITab extends ITabPanel, ITabButton {}

interface IProps {
    className?: string;
    label: string;
    buttonClass?: string;
    tabListClass?: string;
    tabPanelsClass?: string;
    tabPanelClass?: string;
    tabs: ITab[];
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
            <div className={classNames("tabs", className)}>
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
            </div>
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
