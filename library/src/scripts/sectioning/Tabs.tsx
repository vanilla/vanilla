import React, { ReactElement, useState } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { tabClasses } from "@library/sectioning/TabStyles";

interface IData {
    label: string;
    panelData: string;
    contents: React.ReactNode;
}
interface IProps {
    data: IData[];
}

export function DataTabs(props: IProps) {
    const { data } = props;
    const classes = tabClasses();
    const [activeTab, setActiveTab] = useState(0);

    return (
        <Tabs
            className={classes.root}
            onChange={index => {
                setActiveTab(index);
            }}
        >
            <TabList className={classes.tabList}>
                {data.map((tab, index) => {
                    const isActive = activeTab === index;
                    const style = {
                        background: isActive ? "#fff" : "#f5f6f7",
                    };
                    return (
                        <Tab key={index} style={style} className={classes.tab}>
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
        </Tabs>
    );
}
