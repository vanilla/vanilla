/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import UserManagementColumnsConfig from "@dashboard/users/userManagement/UserManagementColumnsConfig";
import { StoryContent } from "@library/storybook/StoryContent";

export default {
    title: "User Management",
    decorators: [dashboardCssDecorator],
};

export function ColumnsConfigurationModal() {
    return (
        <StoryContent>
            <StoryHeading>Configuration modal</StoryHeading>
            <UserManagementColumnsConfig
                configuration={{}}
                treeColumns={["Column 1", "Column 2", "Column 3"]}
                additionalColumns={["Column 4", "Column 5"]}
                onConfigurationChange={() => {}}
                storyBookMode
            />
        </StoryContent>
    );
}

export function ColumnsConfigurationModalInvalidConfig() {
    return (
        <StoryContent>
            <StoryHeading>
                If no columns selected or all hidden, we alert users to at least have one visible.
            </StoryHeading>
            <UserManagementColumnsConfig
                configuration={{}}
                treeColumns={[]}
                additionalColumns={["Column 4", "Column 5"]}
                onConfigurationChange={() => {}}
                storyBookMode
            />
        </StoryContent>
    );
}
