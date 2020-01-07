import React, { ReactElement, useState } from "react";
import { Tabs as ReachTabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { tabClasses } from "@library/sectioning/TabStyles";
import classNames from "classnames";

interface IData {
    label: string;
    panelData: string;
    contents: React.ReactNode;
}
interface IProps {
    data: IData[];
}

export function Tabs(props: IProps) {
    const { data } = props;
    const classes = tabClasses();
    const [activeTab, setActiveTab] = useState(0);

    return (
        <ReachTabs
            className={classes.root}
            onChange={index => {
                setActiveTab(index);
            }}
        >
            <TabList className={classes.tabList}>
                {data.map((tab, index) => {
                    const isActive = activeTab === index;
                    return (
                        <Tab key={index} className={classNames(classes.tab, { [classes.isActive]: isActive })}>
                            <div>{tab.label}</div>
                        </Tab>
                    );
                })}
            </TabList>
            <TabPanels className={classes.tabPanels}>
                {data.map((tab, index) => {
                    return (
                        <TabPanel className={classes.panel} key={index}>
                            {data[index].contents}
                        </TabPanel>
                    );
                })}
            </TabPanels>
        </ReachTabs>
    );
}
