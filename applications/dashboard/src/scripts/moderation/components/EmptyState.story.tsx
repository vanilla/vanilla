/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";

export default {
    title: "Dashboard/Empty State",
    decorators: [dashboardCssDecorator],
};

export function GenericEmptyStateStory() {
    return <EmptyState isStorybook />;
}

export function EmptyStateWithSubtextStory() {
    return <EmptyState subtext={"Here are some instructions on what to do next"} isStorybook />;
}
