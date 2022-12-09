/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { AccountSettings, IAccountSettingsProps } from "@library/accountSettings/AccountSettings";
import merge from "lodash/merge";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import { mockAPI } from "@library/__tests__/utility";

const defaultProps: IAccountSettingsProps = {
    userID: 3,
};

const renderInProvider = (props?: Partial<IAccountSettingsProps>, user?: Partial<IUser>) => {
    const modifiedProps = merge(defaultProps, props);
    const userOverrides = {
        name: "test-user",
        ...user,
    };

    return render(
        <TestReduxProvider
            state={{
                users: {
                    usersByID: {
                        3: {
                            status: LoadStatus.SUCCESS,
                            data: UserFixture.createMockUser(userOverrides),
                        },
                    },
                },
            }}
        >
            <AccountSettings {...modifiedProps} />
        </TestReduxProvider>,
    );
};

describe("AccountSettings", () => {
    it("Display the page header", () => {
        renderInProvider();
        const header = screen.getByRole("heading", { name: "Account & Privacy Settings" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h1");
    });

    it("Display Account section", () => {
        renderInProvider();
        const header = screen.getByRole("heading", { name: "Your Account" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h2");

        expect(screen.getByText("Username")).toBeInTheDocument();
        expect(screen.getByText("test-user")).toBeInTheDocument();
        expect(screen.getByText("Email")).toBeInTheDocument();
        expect(screen.getByText("test@example.com")).toBeInTheDocument();
        expect(screen.getByText("Password")).toBeInTheDocument();
        expect(screen.getByText("﹡﹡﹡﹡﹡﹡﹡﹡﹡﹡﹡﹡")).toBeInTheDocument();
    });

    it("User does not have permission to edit their username", () => {
        renderInProvider();
        const editBtn = screen.queryByRole("button", { name: "Edit Username" });
        expect(editBtn).not.toBeInTheDocument();
    });

    it("User does not have permission to edit their email address", () => {
        renderInProvider();
        const editBtn = screen.queryByRole("button", { name: "Edit Email" });
        expect(editBtn).not.toBeInTheDocument();
    });

    it("User does not have permission to edit their password", () => {
        renderInProvider();
        expect(screen.queryByRole("button", { name: "Edit Password" })).not.toBeInTheDocument();
    });

    it("Display Privacy section", () => {
        renderInProvider({}, { showEmail: true });
        const header = screen.getByRole("heading", { name: "Privacy" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h2");

        const profileDisplayCheckbox = screen.getByRole("checkbox", { name: "Display my profile publicly" });
        expect(profileDisplayCheckbox).toBeInTheDocument();
        expect(profileDisplayCheckbox).toBeChecked();

        const emailDisplayCheckbox = screen.getByRole("checkbox", { name: "Display my email publicly" });
        expect(emailDisplayCheckbox).toBeInTheDocument();
        expect(emailDisplayCheckbox).toBeChecked();
    });

    it("Update email visibility when checkbox is clicked", () => {
        const mockAdapter = mockAPI();
        mockAdapter.onPatch("/users*").replyOnce(200, {});

        renderInProvider({}, { showEmail: false });

        const emailDisplayCheckbox = screen.getByRole("checkbox", { name: "Display my email publicly" });
        expect(emailDisplayCheckbox).toBeInTheDocument();
        expect(emailDisplayCheckbox).not.toBeChecked();

        fireEvent.click(emailDisplayCheckbox);

        expect(mockAdapter.history.patch.length).toBeGreaterThan(0);
    });

    it("Update profile visibility when checkbox is clicked", () => {
        const mockAdapter = mockAPI();
        mockAdapter.onPatch("/users*").replyOnce(200, {});

        renderInProvider({}, { private: false });

        const profileDisplayCheckbox = screen.getByRole("checkbox", { name: "Display my profile publicly" });
        expect(profileDisplayCheckbox).toBeInTheDocument();
        expect(profileDisplayCheckbox).toBeChecked();

        fireEvent.click(profileDisplayCheckbox);

        expect(mockAdapter.history.patch.length).toBeGreaterThan(0);
    });
});
