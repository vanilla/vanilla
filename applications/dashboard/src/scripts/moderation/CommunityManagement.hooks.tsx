/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReason, IReasonPostPatch, PutReportReasonParams } from "@dashboard/moderation/CommunityManagementTypes";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { AxiosResponse } from "axios";

export function useReportReasons() {
    const reasons = useQuery<any, IError, IReason[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/report-reasons`);
            return response.data;
        },
        queryKey: ["reasons"],
        keepPreviousData: true,
    });
    return {
        reasons,
        isLoading: reasons.isLoading,
        error: reasons.error?.response.data as IError,
    };
}

export function useReasonsPostPatch(omitErrorToast?: boolean) {
    const toast = useToast();
    const queryClient = useQueryClient();
    return useMutation<IReason, IError, IReasonPostPatch>({
        mutationFn: async (reason: IReasonPostPatch) => {
            let response: AxiosResponse;
            const { reportReasonID } = reason;
            if (reason.reportReasonID) {
                response = await apiv2.patch(`/report-reasons/${reportReasonID}`, reason.reason);
            } else {
                response = await apiv2.post(`/report-reasons`, reason.reason);
            }
            return response.data;
        },
        mutationKey: ["postPatchReportReason"],
        onSuccess: () => {
            queryClient.invalidateQueries(["reasons"]);
            toast.addToast({ body: t("Report reason changes saved."), autoDismiss: true, dismissible: true });
        },
        onError: () => {
            !omitErrorToast && toast.addToast({ body: t("Error saving reason."), dismissible: true });
        },
    });
}

export function useReasonsDelete() {
    const toast = useToast();
    const queryClient = useQueryClient();
    return useMutation<IReason, IApiError, any>({
        mutationFn: async (reportReasonID: IReason["reportReasonID"]) => {
            const response = await apiv2.delete(`/report-reasons/${reportReasonID}`);
            return response.data;
        },
        mutationKey: ["deleteReportReason"],
        onSuccess: () => {
            queryClient.invalidateQueries(["reasons"]);
            toast.addToast({ body: t("Report reason deleted."), autoDismiss: true, dismissible: true });
        },
        onError: () => {
            toast.addToast({ body: t("Error deleting reason."), dismissible: true });
        },
    });
}

export function useReasonsSort() {
    const toast = useToast();
    const queryClient = useQueryClient();
    return useMutation<IReason, IApiError, any>({
        mutationFn: async (order: PutReportReasonParams) => {
            const response = await apiv2.put(`/report-reasons/sorts`, {
                ...order,
            });
            return response.data;
        },
        mutationKey: ["sortReportReason"],
        onSuccess: () => {
            queryClient.invalidateQueries(["reasons"]);
            toast.addToast({ body: t("Report reason order updated."), autoDismiss: true, dismissible: true });
        },
        onError: () => {
            toast.addToast({ body: t("Error updating reason order."), dismissible: true });
        },
    });
}
