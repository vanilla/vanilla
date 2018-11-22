/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import TabPanels, { ITabPanel } from "@library/components/tabs/pieces/TabPanels";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import TabButtons, { ITabButton } from "@library/components/tabs/pieces/TabButtons";

export interface ITab extends ITabPanel, ITabButton {}

interface IProps {
    className?: string;
    label: string;
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
                <TabButtons
                    tabs={this.props.tabs}
                    selectedTab={this.state.selectedTab}
                    setTab={this.setSelectedTab}
                    getTabButtonID={this.tabButtonID}
                    getTabPanelID={this.tabPanelId}
                    label={this.props.label}
                    parentID={this.id}
                />
                <TabPanels
                    tabs={this.props.tabs}
                    selectedTab={this.state.selectedTab}
                    getTabButtonID={this.tabButtonID}
                    getTabPanelID={this.tabPanelId}
                    parentID={this.id}
                />
            </div>
        );
    }

    private setSelectedTab(selectedTab: number) {
        this.setState({
            selectedTab,
        });
    }

    private tabButtonID = (index: number) => {
        return `${this.id}-tabButton-${index}`;
    };

    private tabPanelId = (index: number) => {
        return `${this.id}-tabPanel-${index}`;
    };
}
