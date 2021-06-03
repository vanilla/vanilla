import React, { useState } from "react";
import { Tabs as ReachTabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { tabStandardClasses, tabBrowseClasses, tabGroupClasses } from "@library/sectioning/tabStyles";
import classNames from "classnames";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { WarningIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { DomNodeAttacher } from "@vanilla/react-utils";
import { cx } from "@emotion/css";

export interface ITabData {
    tabID?: string | number;
    label: string;
    contents?: React.ReactNode;
    contentNodes?: Node[];
    error?: React.ReactNode;
    warning?: React.ReactNode;
    disabled?: boolean;
    [extra: string]: any;
}
interface IProps {
    data: ITabData[];
    tabType?: TabsTypes;
    largeTabs?: boolean;
    extendContainer?: boolean;
    legacyButtons?: boolean;
    onChange?: (newTab: ITabData) => void;
    extraButtons?: React.ReactNode;
    defaultTabIndex?: number;
    includeBorder?: boolean;
    includeVerticalPadding?: boolean;
    tabListClasses?: string;
    tabPanelClasses?: string;
}

export function Tabs(props: IProps) {
    const {
        data,
        tabType,
        defaultTabIndex,
        includeBorder = true,
        includeVerticalPadding = true,
        tabListClasses,
        tabPanelClasses,
    } = props;
    const [activeTab, setActiveTab] = useState(defaultTabIndex ?? 0);
    const classVariants = new Map([
        [TabsTypes.STANDARD, tabStandardClasses()],
        [TabsTypes.BROWSE, tabBrowseClasses()],
        [TabsTypes.GROUP, tabGroupClasses()],
    ]);
    const classes = tabType && classVariants.get(tabType) ? classVariants.get(tabType) : tabStandardClasses();

    return (
        <ReachTabs
            index={activeTab}
            className={classes?.root(props.extendContainer)}
            onChange={(index) => {
                setActiveTab(index);
                props.onChange?.(props.data[index]);
            }}
        >
            <TabList className={cx(classes?.tabList({ includeBorder, isLegacy: props.legacyButtons }), tabListClasses)}>
                {data.map((tab, index) => {
                    const isActive = activeTab === index;
                    return (
                        <Tab
                            key={index}
                            className={classNames(classes?.tab(props.largeTabs, props.legacyButtons), {
                                [classes!.isActive]: isActive,
                            })}
                            disabled={tab.disabled}
                        >
                            <div>{tab.label}</div>
                            {(tab.error || tab.warning) && (
                                <ToolTip label={tab.error || tab.warning}>
                                    <ToolTipIcon>
                                        <WarningIcon
                                            className={
                                                tab.error ? iconClasses().errorFgColor : iconClasses().warningFgColor
                                            }
                                        />
                                    </ToolTipIcon>
                                </ToolTip>
                            )}
                        </Tab>
                    );
                })}
                {props.extraButtons && <div className={classes?.extraButtons}>{props.extraButtons}</div>}
            </TabList>
            <TabPanels className={cx(classes?.tabPanels, tabPanelClasses)}>
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
