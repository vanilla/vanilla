/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import DiscussionOptionsMenu, { addDiscussionOption } from "@library/features/discussions/DiscussionOptionsMenu";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

const renderInProvider = (permissions?: string[]) => {
    const discussion = { ...fakeDiscussions[0], url: "/mockPath", name: "Mock Discussion" };
    render(
        <TestReduxProvider
            state={{
                discussions: {
                    discussionsByID: {
                        10: {
                            status: LoadStatus.SUCCESS,
                            data: discussion,
                        },
                    },
                    deleteStatusesByID: {
                        10: LoadStatus.SUCCESS,
                    },
                    patchStatusByPatchID: {
                        "10-sink": LoadStatus.SUCCESS,
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
        </TestReduxProvider>,
    );
};

describe("Discussion List Options Menu", () => {
    it("Check some default options, each option will depend on permission", async () => {
        renderInProvider();
        const button = await screen.getByRole("button", { name: "Discussion Options" });
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
                permission: "staff.allow",
            },
            component: () => <DropDownItemButton onClick={() => {}}>Option No Permission</DropDownItemButton>,
        });
        addDiscussionOption({
            permission: {
                permission: "articles.add",
            },
            component: () => <DropDownItemButton onClick={() => {}}>Create Article</DropDownItemButton>,
        });

        renderInProvider(["articles.add"]);
        const button = await screen.getByRole("button", { name: "Discussion Options" });
        expect(button).toBeInTheDocument();

        fireEvent.click(button);

        const extraOption = await screen.queryByText("Option No Permission");
        const createArticleOption = await screen.findByText("Create Article");

        expect(extraOption).not.toBeInTheDocument();
        expect(createArticleOption).toBeInTheDocument();
    });
});
