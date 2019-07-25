/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import { mount } from "enzyme";
import SiteNav from "@library/navigation/SiteNav";
import React from "react";
import SiteNavNode, { IActiveRecord } from "@library/navigation/SiteNavNode";
import { INavigationTreeItem } from "@library/@types/api/core";

function renderSiteNav(activeRecord: IActiveRecord) {
    document.body.innerHTML = "<div id='sitenav'></div>";
    return mount(
        <SiteNav activeRecord={activeRecord} bottomCTA={null} collapsible={true}>
            {naviationItems}
        </SiteNav>,
        { attachTo: document.getElementById("siteNav") },
    );
}

describe("<SiteNav />", () => {
    it("initializes with a valid active record.", () => {
        const activeRecord = {
            recordID: 6,
            recordType: "item",
        };

        const siteNav = renderSiteNav(activeRecord);
        expect(siteNav.find(".isCurrent")).to.have.lengthOf(1);

        const siteNavNode = siteNav
            .find(".isCurrent")
            .first()
            .parents(SiteNavNode)
            .first();
        expect(siteNavNode.props()).to.have.property("recordID", activeRecord.recordID);
        expect(siteNavNode.props()).to.have.property("recordType", activeRecord.recordType);
    });

    it("initializes with an invalid active record.", () => {
        const activeRecord = {
            recordID: 999,
            recordType: "item",
        };

        const siteNav = renderSiteNav(activeRecord);
        expect(siteNav.find(".isCurrent")).to.have.lengthOf(0);
    });

    it("changes the active record.", () => {
        const firstActiveRecord = {
            recordID: 6,
            recordType: "item",
        };
        const secondActiveRecord = {
            recordID: 2,
            recordType: "item",
        };

        const siteNav = renderSiteNav(firstActiveRecord);
        expect(siteNav.find(".isCurrent")).to.have.lengthOf(1);

        const firstSiteNavNode = siteNav
            .find(".isCurrent")
            .first()
            .parents(SiteNavNode)
            .first();
        expect(firstSiteNavNode.props()).to.have.property("recordID", firstActiveRecord.recordID);
        expect(firstSiteNavNode.props()).to.have.property("recordType", firstActiveRecord.recordType);

        siteNav.setProps({ activeRecord: secondActiveRecord });
        expect(siteNav.find(".isCurrent")).to.have.lengthOf(1);

        const secondSiteNavNode = siteNav
            .find(".isCurrent")
            .first()
            .parents(SiteNavNode)
            .first();
        expect(secondSiteNavNode.props()).to.have.property("recordID", secondActiveRecord.recordID);
        expect(secondSiteNavNode.props()).to.have.property("recordType", secondActiveRecord.recordType);
    });
});

// Mock navigation data.
const naviationItems: INavigationTreeItem[] = [
    {
        name: "Parent A",
        url: "https://mysite.com/items/parent-a",
        parentID: -1,
        recordID: 1,
        sort: null,
        recordType: "item",
        children: [
            {
                name: "Child A-1",
                url: "https://mysite.com/items/child-a-1",
                parentID: 1,
                recordID: 4,
                sort: null,
                recordType: "item",
                children: [],
            },
            {
                name: "Child A-2",
                url: "https://mysite.com/items/child-a-2",
                parentID: 1,
                recordID: 5,
                sort: null,
                recordType: "item",
                children: [],
            },
            {
                name: "Child A-3",
                url: "https://mysite.com/items/child-a-3",
                parentID: 1,
                recordID: 6,
                sort: null,
                recordType: "item",
                children: [],
            },
        ],
    },
    {
        name: "Parent B",
        url: "https://mysite.com/items/parent-b",
        parentID: -1,
        recordID: 2,
        sort: null,
        recordType: "item",
        children: [],
    },
    {
        name: "Parent C",
        url: "https://mysite.com/items/parent-c",
        parentID: -1,
        recordID: 3,
        sort: null,
        recordType: "item",
        children: [],
    },
];
