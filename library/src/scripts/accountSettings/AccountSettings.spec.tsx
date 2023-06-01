/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { act, cleanup, fireEvent, render, screen } from "@testing-library/react";
import { AccountSettingsImpl } from "@library/accountSettings/AccountSettings";
import { IUser } from "@library/@types/api/users";
import { mockAPI } from "@library/__tests__/utility";
import * as AccountSettingsFixtures from "@library/accountSettings/AccountSettings.fixtures";

const renderByViewingUserStatusWithPermissions = (
    isViewingSelf = true,
    mockUserOverrides?: Partial<IUser>,
    hasEditUsernamesPermission?: boolean,
    editEmailsConfigValue?: boolean,
) => {
    return render(
        isViewingSelf ? (
            <AccountSettingsFixtures.UserEditingSelf
                mockUserOverrides={mockUserOverrides}
                hasEditUsernamesPermission={hasEditUsernamesPermission}
                editEmailsConfigValue={editEmailsConfigValue}
            >
                <AccountSettingsImpl />
            </AccountSettingsFixtures.UserEditingSelf>
        ) : (
            <AccountSettingsFixtures.UserWithEditingPermissionEditingAnotherUser
                mockUserOverrides={mockUserOverrides}
                hasEditUsernamesPermission={hasEditUsernamesPermission}
                editEmailsConfigValue={editEmailsConfigValue}
            >
                <AccountSettingsImpl />
            </AccountSettingsFixtures.UserWithEditingPermissionEditingAnotherUser>
        ),
    );
};

const assertEditButtonsStatus = (
    editUserNameButton: { button: HTMLElement; enabled: boolean },
    editEmailButton: { button: HTMLElement; enabled: boolean },
    editPasswordButton: { button: HTMLElement; enabled: boolean },
) => {
    editUserNameButton.enabled
        ? expect(editUserNameButton.button).not.toBeDisabled()
        : expect(editUserNameButton.button).toBeDisabled();
    editEmailButton.enabled
        ? expect(editEmailButton.button).not.toBeDisabled()
        : expect(editEmailButton.button).toBeDisabled();
    editPasswordButton.enabled
        ? expect(editPasswordButton.button).not.toBeDisabled()
        : expect(editPasswordButton.button).toBeDisabled();
};

describe("AccountSettings", () => {
    afterEach(() => {
        cleanup();
    });
    it("Display the page header", () => {
        renderByViewingUserStatusWithPermissions();
        const header = screen.getByRole("heading", { name: "Account & Privacy Settings" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h1");
    });

    it("Display Account section", () => {
        renderByViewingUserStatusWithPermissions(true, {
            name: "test-user",
            userID: 3,
        });
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

    it("User with no specific permissions, should not be able to edit username/email/password", () => {
        render(
            <AccountSettingsFixtures.UserWithNoSpecificPermissionsEditingAnotherUser>
                <AccountSettingsImpl />
            </AccountSettingsFixtures.UserWithNoSpecificPermissionsEditingAnotherUser>,
        );
        const editUserNameButton = screen.queryByRole("button", { name: "Edit username" });
        expect(editUserNameButton).toBeInTheDocument();
        expect(editUserNameButton).toBeDisabled();

        const editEmailButton = screen.queryByRole("button", { name: "Edit email" });
        expect(editEmailButton).toBeInTheDocument();
        expect(editEmailButton).toBeDisabled();

        const editPasswordButton = screen.queryByRole("button", { name: "Change password" });
        expect(editPasswordButton).toBeInTheDocument();
        expect(editPasswordButton).toBeDisabled();
    });

    it("User (with editUsername permission) viewing own account should be able to edit username/email/password", () => {
        renderByViewingUserStatusWithPermissions();

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: true },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: true },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: true },
        );
    });

    it("User viewing own account does not have editUsername permission and profile.editEmails config is set to false, user should not be able to edit username/email, only password", () => {
        renderByViewingUserStatusWithPermissions(true, undefined, false, false);

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: false },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: false },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: true },
        );
    });

    it("SSOed user (with editUsername permission) viewing own account, should be able to edit username/email but not the password", () => {
        renderByViewingUserStatusWithPermissions(true, { hashMethod: "Random" });

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: true },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: true },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: false },
        );
    });

    it("User (with editUsername permission) is viewing own account, but required to reset the email, should be able to edit username but not the email/password", () => {
        renderByViewingUserStatusWithPermissions(true, { hashMethod: "Reset" });

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: true },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: false },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: false },
        );
    });

    it("User (with editUsers permission) is viewing other user account, username/email/password should be editable, even if editEmails config is false and no edit.userNames permission", () => {
        renderByViewingUserStatusWithPermissions(false, undefined, false, false);

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: true },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: true },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: true },
        );
    });

    it("User (with editUsers permission) is viewing other user account, other user is registered through SSO, still everything should be editable", () => {
        renderByViewingUserStatusWithPermissions(false, { hashMethod: "Random" });

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: true },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: true },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: true },
        );
    });

    it("User (with editUsers permission) is viewing other user account, other user required to reset his password, still everything should be editable", () => {
        renderByViewingUserStatusWithPermissions(false, { hashMethod: "Reset" });

        assertEditButtonsStatus(
            { button: screen.getByRole("button", { name: "Edit username" }), enabled: true },
            { button: screen.getByRole("button", { name: "Edit email" }), enabled: true },
            { button: screen.getByRole("button", { name: "Change password" }), enabled: true },
        );
    });

    it("Display Privacy section", () => {
        render(
            <AccountSettingsFixtures.UserEditingSelf mockUserOverrides={{ showEmail: true }}>
                <AccountSettingsImpl />
            </AccountSettingsFixtures.UserEditingSelf>,
        );

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

    it("Update email visibility when checkbox is clicked", async () => {
        const mockAdapter = mockAPI();
        mockAdapter.onPatch("/users*").replyOnce(200, {});

        render(
            <AccountSettingsFixtures.UserEditingSelf mockUserOverrides={{ showEmail: false }}>
                <AccountSettingsImpl />
            </AccountSettingsFixtures.UserEditingSelf>,
        );

        const emailDisplayCheckbox = screen.getByRole("checkbox", { name: "Display my email publicly" });
        expect(emailDisplayCheckbox).toBeInTheDocument();
        expect(emailDisplayCheckbox).not.toBeChecked();

        await act(async () => {
            fireEvent.click(emailDisplayCheckbox);
        });

        expect(mockAdapter.history.patch.length).toBeGreaterThan(0);
    });

    it("Update profile visibility when checkbox is clicked", async () => {
        const mockAdapter = mockAPI();
        mockAdapter.onPatch("/users*").replyOnce(200, {});

        render(
            <AccountSettingsFixtures.UserEditingSelf mockUserOverrides={{ private: false }}>
                <AccountSettingsImpl />
            </AccountSettingsFixtures.UserEditingSelf>,
        );

        const profileDisplayCheckbox = screen.getByRole("checkbox", { name: "Display my profile publicly" });
        expect(profileDisplayCheckbox).toBeInTheDocument();
        expect(profileDisplayCheckbox).toBeChecked();

        await act(async () => {
            fireEvent.click(profileDisplayCheckbox);
        });

        expect(mockAdapter.history.patch.length).toBeGreaterThan(0);
    });
});
