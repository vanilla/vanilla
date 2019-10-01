/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React, { useEffect } from "react";

/**
 * React storybook decorator to load the dashboard CSS just for that story.
 *
 * @example
 *
 * story.addDecorator(dashboardCssDecorator)
 */
export const dashboardCssDecorator = (getStory: () => any) => {
    require("./_adminStylesNested.scss");
    const AdminStyles = () => {
        return <div className="storybookDashboardStyles">{getStory()}</div>;
    };
    return <AdminStyles />;
};
