/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import DiscussionOptionsMenu, { addDiscussionOption } from "@library/features/discussions/DiscussionOptionsMenu";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";

const renderInProvider = async (permissions?: string[]) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
            },
        },
    });
    const discussion = {
        ...{
            ...DiscussionFixture.fakeDiscussions[0],
            category: {
                categoryID: 1,
                name: "General",
                url: "#",
                allowedDiscussionTypes: ["discussion", "question"],
            },
        },
        url: "/mockPath",
        name: "Mock Discussion",
    };
    await act(async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <TestReduxProvider
                    state={{
                        discussions: {
                            discussionsByID: {
                                [`${discussion.discussionID}`]: {
                                    status: LoadStatus.SUCCESS,
                                    data: discussion,
                                },
                            },
                            deleteStatusesByID: {
                                [`${discussion.discussionID}`]: LoadStatus.SUCCESS,
                            },
                            patchStatusByPatchID: {
                                [`${discussion.discussionID}-sink`]: LoadStatus.SUCCESS,
                            },
                        },
                    }}
                >
                    <PermissionsFixtures.SpecificPermissions
                        permissions={[
                            ...["discussions.moderate", "community.moderate", "discussions.manage", "community.manage"],
                            ...(permissions ?? []),
                        ]}
                    >
                        <DiscussionOptionsMenu discussion={discussion} />
                    </PermissionsFixtures.SpecificPermissions>
                </TestReduxProvider>
            </QueryClientProvider>,
        );
    });
};

describe("Discussion List Options Menu", () => {
    it("Check some default options, each option will depend on permission", async () => {
        await renderInProvider();
        const button = await screen.findByRole("button", { name: "Discussion Options" });
        expect(button).toBeInTheDocument();

        fireEvent.click(button);

        const editOption = await screen.findByText("Edit");
        const moveOption = await screen.findByText("Move");
        const announceOption = await screen.findByText("Announce");
        const deleteOption = await screen.findByText("Delete");
        const sinkOption = await screen.findByText("Sink");
        [editOption, moveOption, announceOption, deleteOption, sinkOption].forEach((option) => {
            expect(option).toBeInTheDocument();
        });
    });

    it("Check some extra options, normally we register them through plugin entries etc, will render only if has permission", async () => {
        addDiscussionOption({
            permission: {
                permission: "some.permission",
            },
            component: () => <DropDownItemButton onClick={() => {}}>Option No Permission</DropDownItemButton>,
        });
        addDiscussionOption({
            permission: {
                permission: "articles.add",
            },
            component: () => <DropDownItemButton onClick={() => {}}>Create Article</DropDownItemButton>,
        });

        await renderInProvider(["articles.add"]);
        const button = await screen.findByRole("button", { name: "Discussion Options" });
        expect(button).toBeInTheDocument();

        fireEvent.click(button);

        const extraOption = screen.queryByText("Option No Permission");
        const createArticleOption = await screen.findByText("Create Article");

        expect(extraOption).not.toBeInTheDocument();
        expect(createArticleOption).toBeInTheDocument();
    });

    it("Check dropdown options order", async () => {
        const expectedItemsByGroups = [
            "Edit",
            "Second Option In First Group",
            "Dismissoff", // "off" is added to the end of the name, in reality its visually hidden
            "Move",
            "Fifth Option In First Group",
            "Delete",
            "Tag",
            "Announce",
            "Change Author",
            "Change Type",
            "Option In Moderation Group",
            "Add to Collection",
            "Bump",
            "Sinkoff", // "off" is added to the end of the name, in reality its visually hidden
            "Option In Status Group",
            "Closeoff", // "off" is added to the end of the name, in reality its visually hidden
            "Revision History",
            "Deleted Comments",
            "Check Analytics Data",
            "Report",
        ];
        addDiscussionOption({
            permission: {
                permission: "staff.allow",
            },
            group: "firstGroup",
            sort: 1,
            component: () => <DropDownItemButton onClick={() => {}}>Second Option In First Group</DropDownItemButton>,
        });
        addDiscussionOption({
            permission: {
                permission: "staff.allow",
            },
            group: "firstGroup",
            sort: 4,
            component: () => <DropDownItemButton onClick={() => {}}>Fifth Option In First Group</DropDownItemButton>,
        });
        addDiscussionOption({
            permission: {
                permission: "staff.allow",
            },
            group: "moderationGroup",
            sort: 3,
            component: () => <DropDownItemButton onClick={() => {}}>Option In Moderation Group</DropDownItemButton>,
        });
        addDiscussionOption({
            permission: {
                permission: "staff.allow",
            },
            group: "statusGroup",
            sort: 2,
            component: () => <DropDownItemButton onClick={() => {}}>Option In Status Group</DropDownItemButton>,
        });

        addDiscussionOption({
            permission: {
                permission: "data.view",
            },
            component: () => <DropDownItemButton onClick={() => {}}>Check Analytics Data</DropDownItemButton>,
        });
        await renderInProvider(["staff.allow", "curation.manage", "data.view"]);
        const button = await screen.getByRole("button", { name: "Discussion Options" });
        expect(button).toBeInTheDocument();

        fireEvent.click(button);

        const dropdownItems = document.querySelector(".dropDownItems");

        let options: string[] = [];

        dropdownItems?.childNodes.forEach((item) => {
            if (item.textContent && item.textContent !== "Loading" && item.textContent !== "") {
                options.push(item.textContent);
            }
        });
        options.forEach((option, index) => {
            expect(index).toBe(expectedItemsByGroups.indexOf(option));
        });
    });
});
