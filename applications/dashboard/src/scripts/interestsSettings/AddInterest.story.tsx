/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AddInterest } from "@dashboard/interestsSettings/AddInterest";
import { ProfileFieldsFixtures } from "@dashboard/userProfiles/components/ProfileFields.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Suggested Content/Add Interest",
};

const queryClient = new QueryClient();

export function Default() {
    const MockProfileFieldsProvider = ProfileFieldsFixtures.createMockProfileFieldsProvider({
        profileFields: ProfileFieldsFixtures.mockProfileFields(),
    });
    return (
        <QueryClientProvider client={queryClient}>
            <MockProfileFieldsProvider>
                <AddInterest forceVisible />
            </MockProfileFieldsProvider>
        </QueryClientProvider>
    );
}
