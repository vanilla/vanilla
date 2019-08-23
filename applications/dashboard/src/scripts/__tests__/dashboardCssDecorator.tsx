import { StoryDecorator } from "@storybook/react";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

/**
 * React storybook decorator to load the dashboard CSS just for that story.
 *
 * @example
 *
 * story.addDecorator(dashboardCssDecorator)
 */
export const dashboardCssDecorator: StoryDecorator = (getStory: () => any) => {
    require("./_adminStylesNested.scss");
    return <div className="storybookDashboardStyles">{getStory()}</div>;
};
