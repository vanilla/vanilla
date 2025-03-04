/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EscalateModal as EscalateModalComponent } from "@dashboard/moderation/components/EscalateModal";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

const queryClient = new QueryClient();

export function EscalateModal() {
    return (
        <QueryClientProvider client={queryClient}>
            <EscalateModalComponent
                recordType={"discussion"}
                escalationType={"report"}
                isVisible={true}
                onClose={() => null}
            />
        </QueryClientProvider>
    );
}
