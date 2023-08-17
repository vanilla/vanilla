/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./NewUserManagementLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function NewUserManagementLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"newUserManagement"}
            name={t("New User Management")}
            description={t(
                "Enable to get a preview of our new user management dashboard with improved search and new configuration options.",
                "Enable to get a preview of our new user management dashboard with improved search and new configuration options.",
            )}
        />
    );
}
