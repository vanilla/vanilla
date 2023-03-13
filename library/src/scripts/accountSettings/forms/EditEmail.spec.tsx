/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { act, render, screen } from "@testing-library/react";
import * as AccountSettingsFixtures from "@library/accountSettings/AccountSettings.fixtures";
import { EditEmail } from "@library/accountSettings/forms/EditEmail";

describe("Edit Email", () => {
    it("Requires a password confirmation if the session user is editing their own profile", async () => {
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf>
                    <EditEmail setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });
        expect(screen.getByText(/New Email/)).toBeInTheDocument();
        expect(screen.getByText(/Password/)).toBeInTheDocument();
    });
    it("Cannot verify an email if the session user does not have the user.edit permission", async () => {
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf>
                    <EditEmail setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
            expect(screen.queryByText(/Verified/)).not.toBeInTheDocument();
        });
    });
    it("Does not ask for passwords if the session user has the user.edit permission", async () => {
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserWithEditingPermissionEditingAnotherUser>
                    <EditEmail setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserWithEditingPermissionEditingAnotherUser>,
            );
            expect(screen.queryByText(/Password/)).not.toBeInTheDocument();
        });
    });
});
