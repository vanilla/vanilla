/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ITabData, ITabsProps, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import Container from "@library/layout/components/Container";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";

interface ITabWidgetProps extends Omit<ITabsProps, "data"> {
    tabs: Array<{
        label: string;
        componentName: string;
        componentProps: Record<string, any>;
    }>;
}

export default function TabWidget(props: ITabWidgetProps) {
    const { tabs } = props;

    const tabsData: ITabData[] = tabs.map(({ label, componentName, componentProps }) => ({
        label,
        contents: (
            <LayoutRenderer
                applyContexts={false}
                layout={[{ $reactComponent: componentName, $reactProps: componentProps }]}
            />
        ),
    }));

    return (
        // Items mounted in a portal break if they don't always return a top level HTML element.
        <div>
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
