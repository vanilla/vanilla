/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { INavigationItem } from "@library/@types/api/core";
import SiteNav from "@library/navigation/SiteNav";
import { SiteNavNodeTypes } from "@library/navigation/SiteNavNodeTypes";

export function DeveloperNav() {
    const items = useDeveloperNavItems();

    return (
        <SiteNav
            initialOpenType="panelMenu"
            initialOpenDepth={2}
            collapsible={true}
            siteNavNodeTypes={SiteNavNodeTypes.DASHBOARD}
            clickableCategoryLabels={true}
        >
            {items}
        </SiteNav>
    );
}

function useDeveloperNavItems(): INavigationItem[] {
    const items: INavigationItem[] = [];

    items.push({
        name: "Performance",
        recordType: "panelMenu",
        recordID: "performance",
        parentID: 0,
        children: [
            {
                name: "Profiles",
                recordType: "developer",
                recordID: "performanceProfiles",
                parentID: "performance",
                url: "/settings/developer/profiles",
            },
        ],
    });

    return items;
}
