/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EscalationActionPanel } from "@dashboard/moderation/components/EscalationActionPanel";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
    decorators: [dashboardCssDecorator],
};

const queryClient = new QueryClient();

export function EscalationActionPanelStory() {
    return (
        <QueryClientProvider client={queryClient}>
            <EscalationActionPanel escalation={CommunityManagementFixture.getEscalation()} />
        </QueryClientProvider>
    );
}
