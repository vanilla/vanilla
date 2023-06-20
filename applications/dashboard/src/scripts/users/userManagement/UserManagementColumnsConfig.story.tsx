/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import UserManagementColumnsConfig from "@dashboard/users/userManagement/UserManagementColumnsConfig";

export default {
    title: "Dashboard/UserManagement",
    decorators: [dashboardCssDecorator],
};

export function ColumnsConfigurationModal() {
    return (
        <>
            <StoryHeading depth={1}>Configuraion modal</StoryHeading>
            <UserManagementColumnsConfig
                configuration={{}}
                treeColumns={["Column 1", "Column 2", "Column 3"]}
                additionalColumns={["Column 4", "Column 5"]}
                onConfigurationChange={() => {}}
                storyBookMode
            />
        </>
    );
}
