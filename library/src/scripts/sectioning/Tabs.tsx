import React, { useState } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { tabClasses } from "@library/sectioning/TabStyles";

interface IDAta {
    label: string;
    panelData: string;
}
interface IProps {
    data: IDAta[];
    children: React.ReactNode;
}

export function DataTabs(props: IProps) {
    const { data } = props;
    const classes = tabClasses();
    const [activeTab, setActiveTab] = useState(0);
    return (
        <Tabs
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
                            {" "}
                            <div>{tab.label}</div>
                        </Tab>
                    );
                })}
            </TabList>
            <TabPanels>
                {data.map((tab, index) => (
                    <TabPanel key={index}>{props.children}</TabPanel>
                ))}
            </TabPanels>
        </Tabs>
    );
}
