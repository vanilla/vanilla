/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { UserCardView } from "@library/features/userCard/UserCard.views";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";

const ProviderWrapper = ({ children }) => {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    permissions: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            isAdmin: true,
                            isSysAdmin: false,
                            permissions: [UserFixture.globalAdminPermissions],
                        },
                    },
                },
            }}
        >
            {children}
        </TestReduxProvider>
    );
};

describe("UserCard", () => {
    it("Displays banned label if user is banned", () => {
        const user = UserFixture.createMockUser({ banned: 1 });
        render(
            <ProviderWrapper>
                <UserCardView user={user} />
            </ProviderWrapper>,
        );
        expect(screen.queryByText(/Banned/)).toBeInTheDocument();
    });
    it("Displays label if only label is provided", () => {
        const user = UserFixture.createMockUser({ label: "Test Label" });
        render(
            <ProviderWrapper>
                <UserCardView user={user} />
            </ProviderWrapper>,
        );
        expect(screen.queryByText(/Test Label/)).toBeInTheDocument();
    });
    it("Displays title if only title is provided", async () => {
        const user = UserFixture.createMockUser({ title: "Test Title" });
        render(
            <ProviderWrapper>
                <UserCardView user={user} />
            </ProviderWrapper>,
        );
        expect(screen.queryByText(/Test Title/)).toBeInTheDocument();
    });
    it("Displays title if both label and title is provided", () => {
        const user = UserFixture.createMockUser({ label: "Test Label", title: "Test Title" });
        render(
            <ProviderWrapper>
                <UserCardView user={user} />
            </ProviderWrapper>,
        );
        expect(screen.queryByText(/Test Title/)).toBeInTheDocument();
    });
    it("Special characters in label is not converted to html entities ", () => {
        const user = UserFixture.createMockUser({ label: `<img src="none" onerror="alert('xss')"/>` });
        const tree = render(
            <ProviderWrapper>
                <UserCardView user={user} />
            </ProviderWrapper>,
        );
        const image = tree.container.getElementsByTagName("img")[0];
        expect(image).toBeTruthy();
    });
    it("Special characters in title is converted to html entities ", () => {
        const user = UserFixture.createMockUser({ title: `<img src="none" onerror="alert('xss')"/>` });
        const tree = render(
            <ProviderWrapper>
                <UserCardView user={user} />
            </ProviderWrapper>,
        );
        const image = tree.container.getElementsByTagName("img")[0];
        expect(image).toBeFalsy();
    });
});
