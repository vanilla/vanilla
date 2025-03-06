/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { EscalateModal } from "@dashboard/moderation/components/EscalateModal";
import { IMessageInfo, MessageAuthorModal } from "@dashboard/moderation/components/MessageAuthorModal";
import { ITriageFilters, TriageFilters } from "@dashboard/moderation/components/TriageFilters";
import { TriageInternalStatus } from "@dashboard/moderation/components/TriageFilters.constants";
import { TriageListItem } from "@dashboard/moderation/components/TriageListItem";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import Loader from "@library/loaders/Loader";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import DocumentTitle from "@library/routing/DocumentTitle";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { ISort, Sort } from "@library/sort/Sort";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useState } from "react";

interface IProps {}

const filterDefaults: ITriageFilters & ISort = {
    internalStatusID: [TriageInternalStatus.UNRESOLVED],
    insertUserID: [],
    insertUserRoleID: [],
    categoryID: [],
    sort: "-recordDateInserted",
};

interface ITriageData {
    results: IDiscussion[];
    pagination: ILinkPages;
    sort: string;
}

const sortOptions = [
    { value: "-dateInserted", name: t("Newest Post") },
    { value: "dateInserted", name: t("Oldest Post") },
];

function TriagePage(props: IProps) {
    const cmdClasses = communityManagementPageClasses();
    const [recordToEscalate, setRecordToEscalate] = useState<IDiscussion | null>(null);

    const [selectedSort, setSelectedSort] = useState<ISelectBoxItem>();
    const [authorMessage, setAuthorMessage] = useState<IMessageInfo | null>(null);

    const initialStatusIDs = useQueryParam("internalStatusID", filterDefaults.internalStatusID);
    const initialRecordUserID = useQueryParam("insertUserID", filterDefaults.insertUserID);
    const initialRecordUserRoleID = useQueryParam("insertUserRoleID", filterDefaults.insertUserRoleID);
    const initialPlaceRecordID = useQueryParam("categoryID", filterDefaults.categoryID);

    const [filters, setFilters] = useState<ITriageFilters>({
        internalStatusID: initialStatusIDs,
        insertUserID: initialRecordUserID,
        insertUserRoleID: initialRecordUserRoleID,
        categoryID: initialPlaceRecordID,
    });

    const handleFilterSet = (newFilters: ITriageFilters) => {
        const tmpFilters = { ...filters, ...newFilters };
        setFilters(tmpFilters);
    };

    const [page, setPage] = useState(useQueryParamPage());

    useQueryStringSync({ ...filters, page, sort: selectedSort?.value }, { ...filterDefaults, page: 1 });

    const triageQuery = useQuery<any, IError, ITriageData>({
        queryFn: async () => {
            const response = await apiv2.get("/discussions", {
                params: {
                    ...filters,
                    expand: ["reportMeta", "category", "attachments", "status"],
                    sort: selectedSort?.value,
                    limit: 100,
                    page,
                },
            });
            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return {
                results: response.data,
                pagination,
            };
        },
        queryKey: ["triageItems", filters, page, selectedSort?.value],
        keepPreviousData: true,
    });

    const paginationProps: INumberedPagerProps = {
        totalResults: triageQuery.data?.pagination?.total,
        currentPage: triageQuery.data?.pagination?.currentPage,
        pageLimit: triageQuery.data?.pagination?.limit,
        hasMorePages: triageQuery.data?.pagination?.total ? triageQuery.data?.pagination?.total >= 10000 : false,
    };

    return (
        <>
            <DocumentTitle title={t("Triage")} />
            <ModerationAdminLayout
                title={t("Triage Dashboard")}
                secondaryBar={
                    <>
                        <Sort
                            sortOptions={sortOptions}
                            selectedSort={selectedSort}
                            onChange={(sort) => {
                                setSelectedSort(sort);
                                if (page !== 1) setPage(1);
                            }}
                        />
                        <NumberedPager
                            className={cmdClasses.pager}
                            isMobile={false}
                            showNextButton={false}
                            onChange={setPage}
                            {...paginationProps}
                        />
                    </>
                }
                rightPanel={<TriageFilters value={filters} onFilter={(newFilters) => handleFilterSet(newFilters)} />}
                content={
                    <>
                        <section className={cmdClasses.content}>
                            {triageQuery.isLoading && (
                                <div>
                                    <Loader />
                                </div>
                            )}
                            {triageQuery.isSuccess && triageQuery.data.results.length === 0 && (
                                <EmptyState subtext={t("New posts matching your filters will appear here")} />
                            )}
                            {triageQuery.isSuccess && (
                                <div className={cmdClasses.list}>
                                    {triageQuery.data.results.map((discussion: IDiscussion) => (
                                        <TriageListItem
                                            key={discussion.discussionID}
                                            discussion={discussion}
                                            onEscalate={(discussion) => {
                                                setRecordToEscalate(discussion);
                                            }}
                                            onMessageAuthor={(userID, url) => setAuthorMessage({ userID, url })}
                                        />
                                    ))}
                                </div>
                            )}
                        </section>
                        {recordToEscalate && (
                            <EscalateModal
                                escalationType={"record"}
                                report={null}
                                recordType={"discussion"}
                                recordID={recordToEscalate.discussionID}
                                record={recordToEscalate ?? null}
                                isVisible={!!recordToEscalate}
                                onClose={() => {
                                    setRecordToEscalate(null);
                                }}
                            />
                        )}
                        <MessageAuthorModal
                            messageInfo={authorMessage}
                            isVisible={!!authorMessage}
                            onClose={() => setAuthorMessage(null)}
                        />
                    </>
                }
            />
        </>
    );
}

export default TriagePage;
