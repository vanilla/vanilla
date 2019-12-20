import React, { useState } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { tabClasses } from "@library/sectioning/TabStyles";
import { TextEditor } from "@library/textEditor/TextEditor";

interface IDAta {
    label: string;
    panelData: string;
}
interface IProps {
    data: IDAta[];
    editor: "";
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
            <TabList>
                {data.map((tab, index) => {
                    const isActive = activeTab === index;
                    const style = {
                        color: isActive ? "grey" : "black",
                    };
                    return (
                        <Tab className={classes.tab} key={index}>
                            <div style={style}>{tab.label}</div>
                        </Tab>
                    );
                })}
            </TabList>
            <TabPanels>
                {data.map((tab, index) => (
                    <TabPanel className={classes.tab} key={index}>
                        <TextEditor />
                    </TabPanel>
                ))}
            </TabPanels>
        </Tabs>
    );
}
