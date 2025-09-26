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
import { IApiError } from "@library/@types/api/core";
import type { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import DateTime, { DateFormats } from "@library/content/DateTime";
import type { VanillaSanitizedHtml } from "@vanilla/dom-utils";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { hashString } from "@vanilla/utils";
import { AxiosResponse } from "axios";
import { useCallback, useEffect, useMemo, useState } from "react";

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
            void queryClient.invalidateQueries(["reasons"]);
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
            void queryClient.invalidateQueries(["reasons"]);
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
            void queryClient.invalidateQueries(["reasons"]);
            toast.addToast({ body: t("Report reason order updated."), autoDismiss: true, dismissible: true });
        },
        onError: () => {
            toast.addToast({ body: t("Error updating reason order."), dismissible: true });
        },
    });
}

export function useReportsQuery(recordType: string | null, recordID: string | null) {
    return useQuery<any, IError, IReport[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/reports`, {
                params: {
                    recordID,
                    recordType,
                    expand: "users",
                },
            });
            return response.data;
        },
        enabled: recordType !== null && recordID !== null,
        queryKey: ["reports", recordType, recordID],
    });
}

export interface IPostRevisionOption {
    value: string;
    recordRevisionDate: string;
    reportIDs: number[];
    recordHtml: VanillaSanitizedHtml;
    recordName: string;
    recordUser: IUserFragment;
    placeRecordName: string;
    placeRecordUrl: string;
    label: React.ReactNode;
    livePost?: IDiscussion | IComment;
}

export function isPostDiscussion(post: IDiscussion | IComment | null | undefined): post is IDiscussion {
    return !!post && "discussionID" in post && !("commentID" in post);
}

export function usePostRevisions(reports: IReport[], livePost: IDiscussion | IComment | null) {
    const options: IPostRevisionOption[] = useMemo(() => {
        const options: IPostRevisionOption[] = [];

        if (livePost) {
            options.push({
                value: "live",
                recordRevisionDate: livePost.dateInserted,
                recordHtml: livePost.body!,
                recordName: livePost.name,
                recordUser: livePost.insertUser ?? deletedUserFragment(),
                placeRecordName: livePost.category?.name ?? "",
                placeRecordUrl: livePost.category?.url ?? "",
                reportIDs: [],
                label: t("Live"),
                livePost,
            });
        }

        reports.forEach((report) => {
            const reportDate = report.dateInserted;

            // Try to find an existing option for this report.
            const existingOption = options.find((option) => option.recordHtml === report.recordHtml);
            if (existingOption) {
                existingOption.reportIDs.push(report.reportID);
                if (new Date(reportDate) < new Date(existingOption.recordRevisionDate)) {
                    existingOption.recordRevisionDate = reportDate;
                    existingOption.label = (
                        <DateTime timestamp={reportDate} type={DateFormats.EXTENDED} mode={"fixed"} />
                    );
                }
            } else {
                options.push({
                    value: `reported-${hashString(report.recordHtml)}`,
                    recordRevisionDate: reportDate,
                    recordHtml: report.recordHtml,
                    recordName: report.recordName,
                    recordUser: report.recordUser ?? deletedUserFragment(),
                    placeRecordName: report.placeRecordName,
                    placeRecordUrl: report.placeRecordUrl,
                    reportIDs: [report.reportID],
                    label: <DateTime timestamp={reportDate} type={DateFormats.EXTENDED} mode={"fixed"} />,
                });
            }
        });

        return options;
    }, [reports, livePost]);

    const [selectedRevisionValue, setSelectedRevisionValue] = useState<string | undefined>(options[0]?.value);

    useEffect(() => {
        // Our post has been updated, so we need to update the selected revision.
        setSelectedRevisionValue(options[0]?.value);
    }, [livePost]);

    const selectedRevision = options.find((option) => option.value === selectedRevisionValue);

    function setReportRevisionActive(reportID: number) {
        const revision = options.find((option) => option.reportIDs.includes(reportID));
        if (revision) {
            setSelectedRevisionValue(revision.value);
        }
    }

    return {
        options,
        selectedRevision,
        setSelectedRevisionValue,
        setReportRevisionActive,
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
        onSuccess: async () => {
            await queryClient.invalidateQueries(["escalations", escalationID]);
            await queryClient.invalidateQueries(["escalations"]);
            await queryClient.invalidateQueries(["livePost"]);
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
            void queryClient.invalidateQueries(["escalationComments", escalationID]);
        },
        onError: () => {
            toast.addToast({ body: t("Error posting comment."), dismissible: true });
        },
    });
}
