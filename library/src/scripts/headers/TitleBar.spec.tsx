/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { screen } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { filterNavItemsByRole } from "@library/headers/TitleBar.ParamContext";

const MOCK_NAV_ITEMS = [
    {
        name: "No Nested",
        url: "/no-nested",
        id: "no-nested",
    },
    {
        name: "No Nested With Role",
        url: "/no-nested-with-role",
        id: "no-nested-with-role",
        roleIDs: [2],
    },
    {
        name: "Nested",
        id: "nested",
        url: "/nested",
        children: [
            {
                name: "Nested 1",
                url: "/nested-1",
                id: "nested-1",
                children: [
                    {
                        name: "Nested 1.1",
                        url: "/nested-1-1",
                        id: "nested-1-1",
                        roleIDs: [2],
                    },
                ],
            },
            {
                name: "Nested 2 With Role",
                url: "/nested-2-with-role",
                id: "nested-2-with-role",
                roleIDs: [2],
            },
        ],
    },
];

describe("TitleBar", async () => {
    it("TitleBar.ParamContext - filterNavItemsByRole() -For top level nav items and its children, we respect visibility by role", () => {
        const userNoPermission = UserFixture.createMockUser({
            roleIDs: [1],
        });
        const userWithPermission = UserFixture.createMockUser({
            roleIDs: [1, 2],
        });

        const visibleItemsNoPermission = filterNavItemsByRole(MOCK_NAV_ITEMS, userNoPermission);
        expect(visibleItemsNoPermission).toHaveLength(2);
        expect(visibleItemsNoPermission[0].name).toBe("No Nested");
        expect(visibleItemsNoPermission[1].name).toBe("Nested");
        expect(visibleItemsNoPermission[1].children).toHaveLength(1);
        expect(visibleItemsNoPermission[1].children?.[0].children).toEqual([]);

        const visibleItemsWithPermission = filterNavItemsByRole(MOCK_NAV_ITEMS, userWithPermission);
        expect(visibleItemsWithPermission).toStrictEqual(MOCK_NAV_ITEMS);
    });
});
