/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Widget } from "@library/features/homepages/Widget";
import { ITabData, ITabsProps, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import Container from "@library/layout/components/Container";
import { useWidgetLayoutClasses } from "@library/layout/WidgetLayout.context";

interface ITabWidgetProps extends Omit<ITabsProps, "data"> {
    tabs: Array<{
        label: string;
        componentName: string;
        componentProps: Record<string, any>;
    }>;
}

export default function TabWidget(props: ITabWidgetProps) {
    const { tabs } = props;

    const tabsData: ITabData[] = tabs.map(({ label, componentName, componentProps }, index) => ({
        label,
        contents: <Widget key={index} $type={componentName} {...componentProps} />,
    }));

    const widgetClasses = useWidgetLayoutClasses();

    return (
        <div className={widgetClasses.widgetClass}>
            <Container fullGutter>
                <Tabs
                    includeVerticalPadding={false}
                    includeBorder={false}
                    largeTabs
                    tabType={TabsTypes.BROWSE}
                    data={tabsData}
                    extendContainer
                    {...props}
                />
            </Container>
        </div>
    );
}
