/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useEffect, useState } from "react";
import { Tabs as ReachTabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { tabStandardClasses, tabBrowseClasses, tabGroupClasses } from "@library/sectioning/tabStyles";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { InformationIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { DomNodeAttacher, mountPortal } from "@vanilla/react-utils";
import { cx } from "@emotion/css";
import { Icon } from "@vanilla/icons";
import TruncatedText from "@library/content/TruncatedText";

export interface ITabData {
    tabID?: string | number;
    label: React.ReactNode;
    contents?: React.ReactNode;
    contentNodes?: Node[];
    error?: React.ReactNode;
    warning?: React.ReactNode;
    disabled?: boolean;
    [extra: string]: any;
}
export interface ITabsProps {
    data: ITabData[];
    activeTab?: number;
    setActiveTab?: (newActiveTab: number) => void;
    tabType?: TabsTypes;
    largeTabs?: boolean;
    extendContainer?: boolean;
    legacyButtons?: boolean;
    onChange?: (newTab: ITabData) => void | Promise<void>;
    extraButtons?: React.ReactNode;
    defaultTabIndex?: number;
    includeBorder?: boolean;
    includeVerticalPadding?: boolean;
    tabListClasses?: string;
    tabPanelClasses?: string;
    tabsRootClass?: string;
    tabClass?: string;
    activeTabIndex?: number;
    setActiveTabIndex?: (index: number) => void;
}

function PassThruKludge(props: any) {
    return <>{props.children}</>;
}

export function Tabs(props: ITabsProps) {
    const {
        data,
        tabType,
        defaultTabIndex,
        includeBorder = true,
        includeVerticalPadding = true,
        tabListClasses,
        tabPanelClasses,
        tabsRootClass,
        tabClass,
    } = props;
    const [ownActiveTab, ownSetActiveTab] = useState(defaultTabIndex ?? 0);
    const activeTab = props.activeTab ?? ownActiveTab;
    const setActiveTab = props.setActiveTab ?? ownSetActiveTab;

    useEffect(() => {
        if (props.defaultTabIndex !== undefined && activeTab !== props.defaultTabIndex) {
            setActiveTab(props.defaultTabIndex);
        }
    }, [props.defaultTabIndex]);

    const classVariants = new Map([
        [TabsTypes.STANDARD, tabStandardClasses()],
        [TabsTypes.BROWSE, tabBrowseClasses()],
        [TabsTypes.GROUP, tabGroupClasses()],
    ]);
    const classes = tabType && classVariants.get(tabType) ? classVariants.get(tabType) : tabStandardClasses();

    let tabList = (
        <TabList className={cx(classes?.tabList({ includeBorder, isLegacy: props.legacyButtons }), tabListClasses)}>
            {data.map((tab, index) => {
                const isActive = activeTab === index;
                return (
                    <Tab
                        key={index}
                        className={cx(
                            classes?.tab(props.largeTabs, props.legacyButtons),
                            {
                                [classes!.isActive]: isActive,
                            },
                            tabClass,
                        )}
                        disabled={tab.disabled}
                    >
                        <TruncatedText lines={1} maxCharCount={25}>
                            {tab.label}
                        </TruncatedText>

                        {(tab.error || tab.warning) && (
                            <ToolTip label={tab.error || tab.warning}>
                                <ToolTipIcon>
                                    <Icon
                                        className={
                                            tab.error ? iconClasses().errorFgColor : iconClasses().warningFgColor
                                        }
                                        icon={"status-warning"}
                                        size={"compact"}
                                    />
                                </ToolTipIcon>
                            </ToolTip>
                        )}
                        {tab.info && (
                            <ToolTip label={tab.info}>
                                <ToolTipIcon>
                                    <InformationIcon />
                                </ToolTipIcon>
                            </ToolTip>
                        )}
                    </Tab>
                );
            })}
            {/* Need to have "disabled" given to make sure this isn't parsed as a tab. */}
            <PassThruKludge disabled>
                {props.extraButtons ? <div className={classes?.extraButtons}>{props.extraButtons}</div> : null}
            </PassThruKludge>
        </TabList>
    );

    return (
        <ReachTabs
            index={activeTab}
            className={cx(classes?.root(props.extendContainer), tabsRootClass)}
            onChange={(index) => {
                setActiveTab(index);
                props.onChange?.(props.data[index]);
            }}
        >
            {tabList}
            <TabPanels className={cx(classes?.tabPanels, tabPanelClasses, "tabContent")}>
                {data.map((tab, index) => {
                    return (
                        <TabPanel className={classes?.panel({ includeVerticalPadding })} key={index}>
                            {tab.contents && tab.contents}
                            {tab.contentNodes && <DomNodeAttacher nodes={tab.contentNodes} />}
                        </TabPanel>
                    );
                })}
            </TabPanels>
        </ReachTabs>
    );
}
