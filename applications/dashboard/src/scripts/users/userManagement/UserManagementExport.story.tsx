/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { UserExportToast } from "@dashboard/users/userManagement/UserManagementExport";
import { Toast } from "@library/features/toaster/Toast";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";

export default {
    title: "User Management",
};

export function ExportToast() {
    return (
        <StoryContent>
            <StoryHeading>Export toast with some progerss made</StoryHeading>
            <Toast wide visibility>
                <UserExportToast countTotal={9432} countFetched={3000} />
            </Toast>
            <StoryHeading>Export toast with more than 10000 total records</StoryHeading>
            <Toast wide visibility>
                <UserExportToast countTotal={10000} countFetched={142000} />
            </Toast>
            <StoryHeading>Export toast with an unknown number of records</StoryHeading>
            <Toast wide visibility>
                <UserExportToast countTotal={0} countFetched={20000} />
            </Toast>
        </StoryContent>
    );
}
