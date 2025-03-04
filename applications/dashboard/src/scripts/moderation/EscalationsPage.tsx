/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { useEscalationMutation } from "@dashboard/moderation/CommunityManagement.hooks";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { EscalationStatus, IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { DisabledBanner } from "@dashboard/moderation/components/DisabledBanner";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { EscalationFilters, IEscalationFilters } from "@dashboard/moderation/components/EscalationFilters";
import { EscalationListItem } from "@dashboard/moderation/components/EscalationListItem";
import { IMessageInfo, MessageAuthorModal } from "@dashboard/moderation/components/MessageAuthorModal";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import Loader from "@library/loaders/Loader";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import DocumentTitle from "@library/routing/DocumentTitle";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { Sort } from "@library/sort/Sort";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useState } from "react";

interface IProps {}

interface IEscalationQueryData {
    results: IEscalation[];
    pagination: ILinkPages;
}

const sortOptions = [
    { value: "-dateInserted", name: t("Newest Escalation") },
    { value: "dateInserted", name: t("Oldest Escalation") },
];

const defaultValues = {
    statuses: [EscalationStatus.OPEN, EscalationStatus.IN_PROGRESS],
    reportReasonID: [],
    assignedUserID: [],
    recordUserID: [],
    recordUserRoleID: [],
    sort: "-dateInserted",
    page: 1,
};

function EscalationsPage(props: IProps) {
    const initialStatuses = useQueryParam("statuses", defaultValues.statuses);
    const initialReportReasonID = useQueryParam("reportReasonID", defaultValues.reportReasonID);
    const initialAssignedUserID = useQueryParam("assignedUserID", defaultValues.assignedUserID);
    const initialRecordUserID = useQueryParam("recordUserID", defaultValues.recordUserID);
    const initialRecordUserRoleID = useQueryParam("recordUserRoleID", defaultValues.recordUserRoleID);

    const [filters, setFilters] = useState<IEscalationFilters>({
        statuses: initialStatuses,
        reportReasonID: initialReportReasonID,
        assignedUserID: initialAssignedUserID,
        recordUserID: initialRecordUserID,
        recordUserRoleID: initialRecordUserRoleID,
    });

    const cmdClasses = communityManagementPageClasses();

    const [authorMessage, setAuthorMessage] = useState<IMessageInfo | null>(null);

    const [selectedSort, setSelectedSort] = useState<ISelectBoxItem>();

    const [page, setPage] = useState(useQueryParamPage());

    useQueryStringSync({ ...filters, page, sort: selectedSort?.value }, defaultValues);

    const escalationMutation = useEscalationMutation();

    const escalationsQuery = useQuery<any, IError, IEscalationQueryData>({
        queryFn: async () => {
            const response = await apiv2.get("/escalations", {
                params: {
                    ...filters,
                    expand: "users",
                    limit: 100,
                    status: filters.statuses,
                    sort: selectedSort?.value ?? defaultValues.sort,
                    page: page ?? defaultValues.page,
                },
            });
            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return { results: response.data ?? [], pagination };
        },
        queryKey: ["escalations", page, selectedSort?.value, filters],
        keepPreviousData: true,
    });

    const paginationProps: INumberedPagerProps = {
        totalResults: escalationsQuery.data?.pagination?.total,
        currentPage: escalationsQuery.data?.pagination?.currentPage,
        pageLimit: escalationsQuery.data?.pagination?.limit,
        hasMorePages: escalationsQuery.data?.pagination?.total
            ? escalationsQuery.data?.pagination?.total >= 10000
            : false,
    };

    return (
        <>
            <DocumentTitle title={t("Escalations")} />

            <ModerationAdminLayout
                preTitle={<DisabledBanner />}
                title={t("Escalations Dashboard")}
                rightPanel={
                    <EscalationFilters
                        value={filters}
                        onFilter={(newFilters) =>
                            setFilters((prev) => {
                                return {
                                    ...prev,
                                    ...newFilters,
                                };
                            })
                        }
                    />
                }
                secondaryBar={
                    <>
                        <span>
                            <Sort sortOptions={sortOptions} selectedSort={selectedSort} onChange={setSelectedSort} />
                        </span>
                        <NumberedPager
                            className={cmdClasses.pager}
                            isMobile={false}
                            {...paginationProps}
                            showNextButton={false}
                            onChange={setPage}
                        />
                    </>
                }
                content={
                    <>
                        <section className={cmdClasses.content}>
                            {escalationsQuery.isLoading && (
                                <div>
                                    <Loader />
                                </div>
                            )}
                            {escalationsQuery.isSuccess && escalationsQuery.data.results.length === 0 && (
                                <EmptyState subtext={t("Escalations matching your filters will appear here")} />
                            )}
                            {escalationsQuery.isSuccess && (
                                <div className={cmdClasses.list}>
                                    {escalationsQuery.data.results.map((escalation) => (
                                        <EscalationListItem
                                            onAttachmentCreated={(attachmentCatalog) => {
                                                if (attachmentCatalog.escalationStatusID) {
                                                    // Enable that statusID
                                                    setFilters((prev) => {
                                                        if (
                                                            attachmentCatalog.escalationStatusID &&
                                                            !prev.statuses.includes(
                                                                attachmentCatalog.escalationStatusID,
                                                            )
                                                        ) {
                                                            return {
                                                                ...prev,
                                                                statuses: prev.statuses.concat([
                                                                    attachmentCatalog.escalationStatusID,
                                                                ]),
                                                            };
                                                        } else {
                                                            return prev;
                                                        }
                                                    });
                                                }
                                            }}
                                            key={escalation.escalationID}
                                            escalation={escalation}
                                            onMessageAuthor={(messageInfo) => setAuthorMessage(messageInfo)}
                                            onRecordVisibilityChange={(recordIsLive) =>
                                                escalationMutation.mutateAsync({
                                                    escalationID: escalation.escalationID,
                                                    payload: { recordIsLive },
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            )}
                        </section>
                        {authorMessage && (
                            <MessageAuthorModal
                                messageInfo={authorMessage}
                                isVisible={!!authorMessage}
                                onClose={() => setAuthorMessage(null)}
                            />
                        )}
                    </>
                }
            />
            <MessageAuthorModal
                messageInfo={authorMessage}
                isVisible={!!authorMessage}
                onClose={() => setAuthorMessage(null)}
            />
        </>
    );
}

export default EscalationsPage;
