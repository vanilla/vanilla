/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
/**
 * React storybook decorator to load the legacy CSS just for that story.
 *
 * @example
 *
 * story.addDecorator(legacyCssDecorator)
 */
export const legacyCssDecorator = (getStory: () => any) => {
    require("./_legacyStylesNested.scss");
    const AdminStyles = () => {
        return <div className="storybookLegacyStyles">{getStory()}</div>;
    };
    return <AdminStyles />;
};
