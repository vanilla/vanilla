/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INavigationTreeItem } from "@library/@types/api/core";
import SiteNav from "@library/navigation/SiteNav";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import { render, screen } from "@testing-library/react";

function RenderSiteNav({ recordID }: { recordID: IActiveRecord["recordID"] }) {
    return (
        <SiteNav activeRecord={{ recordID, recordType: "item" }} collapsible={true}>
            {navigationItems}
        </SiteNav>
    );
}

function getActiveLink() {
    return screen.getByRole("link", { current: "page" });
}

describe("SiteNav", () => {
    it("initializes with a valid active record", () => {
        render(<RenderSiteNav recordID={6} />);
        const activeLink = getActiveLink();
        expect(activeLink).toBeInTheDocument();
        expect(activeLink).toHaveTextContent(/Child A-3/);
        expect(activeLink).toHaveAttribute("href", "https://mysite.com/items/child-a-3");
    });

    it("initializes with an invalid active record", () => {
        render(<RenderSiteNav recordID={999} />);

        expect(screen.getByText(/Parent B/)).toBeInTheDocument();
        expect(screen.getByText(/Child A-2/)).toBeInTheDocument();

        const activeLink = screen.queryByRole("link", { current: "page" });
        expect(activeLink).toBeNull();
    });

    it("changes the active record", () => {
        const { rerender } = render(<RenderSiteNav recordID={6} />);

        const firstActive = getActiveLink();
        expect(firstActive).toBeInTheDocument();
        expect(firstActive).toHaveTextContent(/Child A-3/);
        expect(firstActive).toHaveAttribute("href", "https://mysite.com/items/child-a-3");

        rerender(<RenderSiteNav recordID={2} />);

        const secondActive = getActiveLink();
        expect(secondActive).toBeInTheDocument();
        expect(secondActive).toHaveTextContent(/Parent B/);
        expect(secondActive).toHaveAttribute("href", "https://mysite.com/items/parent-b");
    });
});

// Mock navigation data.
const navigationItems: INavigationTreeItem[] = [
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
