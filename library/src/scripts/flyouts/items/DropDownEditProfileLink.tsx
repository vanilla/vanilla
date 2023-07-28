/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getMeta, t } from "@library/utility/appUtils";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import Permission from "@library/features/users/Permission";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";

/**
 * Add link to edit profile, with extra permission checks to render correct link
 */
export function DropDownEditProfileLink() {
    const customFieldsEnabled = getMeta("featureFlags.CustomProfileFields.Enabled");

    return (
        <Permission
            permission={"profiles.edit"}
            fallback={
                <>
                    <DropDownItemSeparator />
                    <DropDownItemLink to="/profile/preferences" name={t("Edit Preferences")} />
                </>
            }
        >
            <DropDownItemSeparator />
            <DropDownItemLink
                to={customFieldsEnabled ? "/profile/account-privacy" : "/profile/edit"}
                name={t(customFieldsEnabled ? "Account & Privacy Settings" : "Edit Profile")}
            />
        </Permission>
    );
}
