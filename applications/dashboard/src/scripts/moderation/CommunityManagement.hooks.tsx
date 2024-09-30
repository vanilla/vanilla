/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    IEscalation,
    IReason,
    IReasonPostPatch,
    IReport,
    PutReportReasonParams,
} from "@dashboard/moderation/CommunityManagementTypes";
import { usePostRevision } from "@dashboard/moderation/PostRevisionContext";

import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { AxiosResponse } from "axios";
import { useCallback, useMemo } from "react";

export function useReportReasons(params?: { includeSystem?: boolean }) {
    const reasons = useQuery<any, IError, IReason[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/report-reasons`, {
                params: params,
            });
            return response.data;
        },
        queryKey: ["reasons", params],
        keepPreviousData: true,
    });
    return {
        reasons,
        isLoading: reasons.isLoading,
        error: reasons.error?.response.data as IError,
    };
}

export function useReasonMutation(omitErrorToast?: boolean) {
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

export function useReasonsDeleteMutation() {
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

export function useReasonsSortMutation() {
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

/**
 * Get select box state for revisions of the current record
 */
export function useRevisionOptions() {
    const { reports, activeReport, mostRecentRevision } = usePostRevision();

    const options = useMemo<ISelectBoxItem[]>(() => {
        if (mostRecentRevision) {
            // Always present the most recent revision an option
            let options = [
                {
                    value: -1,
                    dateInserted: mostRecentRevision.dateUpdated ?? mostRecentRevision.dateInserted,
                },
            ];
            if (reports) {
                // Append reports with unique recordHtml lengths
                options = [
                    ...options,
                    ...new Map(
                        reports.map((report) => [
                            report.recordHtml.length,
                            {
                                value: report.reportID,
                                dateInserted: report.dateInserted,
                            },
                        ]),
                    ).values(),
                ];
            }
            // Format options for select component
            return options.map((option) => {
                return {
                    value: `${option.value}`,
                    name: <DateTime type={DateFormats.EXTENDED} mode={"fixed"} timestamp={option.dateInserted} />,
                };
            });
        }
        return [];
    }, [mostRecentRevision, reports]);

    const selectedOption = useMemo<ISelectBoxItem | undefined>(() => {
        if (activeReport && options) {
            const option = options.find((option) => option.value === `${activeReport.reportID}`);
            return option;
        } else if (activeReport === null && options) {
            const option = options.find((option) => option.value === "-1");
            return option;
        }
        return undefined;
    }, [activeReport]);

    return {
        options,
        selectedOption,
    };
}

export function useEscalationQuery(escalationID: IEscalation["escalationID"]) {
    const escalation = useQuery<any, IError, IEscalation>({
        queryFn: async () => {
            const response = await apiv2.get(`/escalations/${escalationID}?expand=users`);
            return response.data;
        },
        queryKey: ["escalations", escalationID],
        keepPreviousData: true,
    });
    return escalation;
}

export function useEscalationMutation(escalationID?: IEscalation["escalationID"]) {
    const toast = useToast();
    const queryClient = useQueryClient();
    return useMutation<IEscalation, IApiError, any>({
        mutationFn: async (params: { escalationID?: IEscalation["escalationID"]; payload: Partial<IEscalation> }) => {
            const response = await apiv2.patch(`/escalations/${escalationID ?? params.escalationID}`, {
                ...params.payload,
            });
            return response.data;
        },
        mutationKey: ["escalationPatch", escalationID],
        onSuccess: () => {
            queryClient.invalidateQueries(["escalations", escalationID]);
            queryClient.invalidateQueries(["escalations"]);
            toast.addToast({ body: t("Escalation Updated"), autoDismiss: true, dismissible: true });
        },
        onError: () => {
            toast.addToast({ body: t("Error updating escalation."), dismissible: true });
        },
    });
}

export function useEscalationCommentsQuery(escalationID: IEscalation["escalationID"]) {
    return useQuery<any, IError, IComment[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/comments`, {
                params: {
                    expand: "users",
                    parentRecordType: "escalation",
                    parentRecordID: escalationID,
                    page: 1,
                    limit: 500,
                },
            });
            return response.data;
        },
        queryKey: ["escalationComments", escalationID],
        keepPreviousData: true,
    });
}

export function useEscalationCommentMutation(escalationID: IEscalation["escalationID"]) {
    const toast = useToast();
    const queryClient = useQueryClient();
    return useMutation<any, IApiError, any>({
        mutationFn: async (value: string) => {
            const response = await apiv2.post(`/comments`, {
                parentRecordType: "escalation",
                parentRecordID: escalationID,
                format: "rich2",
                body: value,
            });
            return response.data;
        },
        mutationKey: ["escalationComment", escalationID],
        onSuccess: () => {
            queryClient.invalidateQueries(["escalationComments", escalationID]);
        },
        onError: () => {
            toast.addToast({ body: t("Error posting comment."), dismissible: true });
        },
    });
}
