/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { StoryDecorator } from "@storybook/react";
import React, { useEffect } from "react";

/**
 * React storybook decorator to load the dashboard CSS just for that story.
 *
 * @example
 *
 * story.addDecorator(dashboardCssDecorator)
 */
export const dashboardCssDecorator: StoryDecorator = (getStory: () => any) => {
    require("./_adminStylesNested.scss");
    const AdminStyles = () => {
        useEffect(() => {
            document.body.classList.add("storybookDashboardStyles");
            () => {
                document.body.classList.remove("storybookDashboardStyles");
            };
        });
        return getStory();
    };
    return <AdminStyles />;
};
