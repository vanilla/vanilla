/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { EscalateModal as EscalateModalComponent } from "@dashboard/moderation/components/EscalateModal";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";

export default {
    title: "Dashboard/Community Management",
};

const queryClient = new QueryClient();

export function EscalateModal() {
    return (
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <QueryClientProvider client={queryClient}>
                    <EscalateModalComponent
                        recordType={"discussion"}
                        escalationType={"report"}
                        isVisible={true}
                        onClose={() => null}
                    />
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>
    );
}
