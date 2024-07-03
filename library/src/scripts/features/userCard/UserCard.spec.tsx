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

describe("UserCard", () => {
    it("Displays banned label if user is banned", () => {
        const user = UserFixture.createMockUser({ banned: 1 });
        render(
            <TestReduxProvider>
                <UserCardView user={user} />
            </TestReduxProvider>,
        );
        expect(screen.queryByText(/Banned/)).toBeInTheDocument();
    });
    it("Displays label if only label is provided", () => {
        const user = UserFixture.createMockUser({ label: "Test Label" });
        render(
            <TestReduxProvider>
                <UserCardView user={user} />
            </TestReduxProvider>,
        );
        expect(screen.queryByText(/Test Label/)).toBeInTheDocument();
    });
    it("Displays title if only title is provided", async () => {
        const user = UserFixture.createMockUser({ title: "Test Title" });
        render(
            <TestReduxProvider>
                <UserCardView user={user} />
            </TestReduxProvider>,
        );
        expect(screen.queryByText(/Test Title/)).toBeInTheDocument();
    });
    it("Displays title if both label and title is provided", () => {
        const user = UserFixture.createMockUser({ label: "Test Label", title: "Test Title" });
        render(
            <TestReduxProvider>
                <UserCardView user={user} />
            </TestReduxProvider>,
        );
        expect(screen.queryByText(/Test Title/)).toBeInTheDocument();
    });
    it("Special characters in label is not converted to html entities ", () => {
        const user = UserFixture.createMockUser({
            label: `<img data-testid="testImage" src="none" onerror="alert('xss')"/>`,
        });
        render(
            <TestReduxProvider>
                <UserCardView user={user} />
            </TestReduxProvider>,
        );
        const image = screen.queryByTestId("testImage");
        expect(image).toHaveAttribute("src", "none");
        expect(image).toHaveAttribute("onerror", "alert('xss')");
    });
    it("Special characters in title is converted to html entities ", () => {
        const user = UserFixture.createMockUser({
            title: `<img data-testid="testImage" src="none" onerror="alert('xss')"/>`,
        });
        render(
            <TestReduxProvider>
                <UserCardView user={user} />
            </TestReduxProvider>,
        );
        const sanitized = screen.queryByText(/img.*/);
        expect(sanitized).toBeInTheDocument();
    });
});
