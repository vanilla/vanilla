/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useQuery } from "@tanstack/react-query";

export function useReportReasons() {
    const reasons = useQuery<any, IError, IReason[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/reports/reasons`);
            return response.data;
        },
        queryKey: ["reasons"],
    });
    return {
        reasons,
        isLoading: reasons.isLoading,
    };
}
