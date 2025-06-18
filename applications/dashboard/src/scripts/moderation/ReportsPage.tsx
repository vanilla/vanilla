/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import type { IComment } from "@dashboard/@types/api/comment";
import type { IDiscussion } from "@dashboard/@types/api/discussion";
import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { IReport, IReportsData } from "@dashboard/moderation/CommunityManagementTypes";
import { DisabledBanner } from "@dashboard/moderation/components/DisabledBanner";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { IReportFilters, ReportFilters } from "@dashboard/moderation/components/ReportFilters";
import { ReportStatus } from "@dashboard/moderation/components/ReportFilters.constants";
import { ReportListItem } from "@dashboard/moderation/components/ReportListItem";
import apiv2 from "@library/apiv2";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { Row } from "@library/layout/Row";
import Loader from "@library/loaders/Loader";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import DocumentTitle from "@library/routing/DocumentTitle";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { Sort } from "@library/sort/Sort";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useState } from "react";

const defaultFilterValues = {
    statuses: [ReportStatus.NEW],
    reportReasonID: [],
    insertUserID: [],
    insertUserRoleID: [],
    recordUserID: [],
    sort: "-dateInserted",
    page: 1,
    recordType: undefined,
    recordID: undefined,
};

const sortOptions = [
    { value: "-dateInserted", name: t("Newest Report") },
    { value: "dateInserted", name: t("Oldest Report") },
];

export function ReportsPage() {
    const cmdClasses = communityManagementPageClasses();

    const initialStatuses = useQueryParam<string | string[]>("statuses", defaultFilterValues.statuses);
    const initialReasons = useQueryParam("reportReasonID", defaultFilterValues.reportReasonID);
    const initialInsertUserID = useQueryParam("insertUserID", defaultFilterValues.insertUserID);
    const initialInsertUserRoleID = useQueryParam("insertUserRoleID", defaultFilterValues.insertUserRoleID);
    const initialRecordUserID = useQueryParam("recordUserID", defaultFilterValues.recordUserID);
    const initialRecordType = useQueryParam("recordType", defaultFilterValues.recordType);
    const initialRecordID = useQueryParam("recordID", defaultFilterValues.recordID);

    const [filters, setFilters] = useState<IReportFilters>({
        statuses: initialStatuses === "none" ? [] : [ReportStatus.NEW],
        reportReasonID: initialReasons,
        insertUserID: initialInsertUserID,
        insertUserRoleID: initialInsertUserRoleID,
        recordUserID: initialRecordUserID,
        recordType: initialRecordType,
        recordID: initialRecordID,
    });

    const [selectedSort, setSelectedSort] = useState<ISelectBoxItem>();

    const [page, setPage] = useState(useQueryParamPage());

    useQueryStringSync({ ...filters, sort: selectedSort?.value, page }, defaultFilterValues);

    const reports = useQuery<any, IError, IReportsData>({
        queryFn: async () => {
            if (!filters.recordType) {
                delete filters.recordType;
                delete filters.recordID;
            }

            if (!filters.recordID) {
                delete filters.recordID;
            }

            const response = await apiv2.get("/reports", {
                params: {
                    ...filters,
                    expand: "users",
                    status: filters.statuses,
                    sort: selectedSort?.value ?? defaultFilterValues.sort,
                    limit: 10,
                    page,
                },
            });

            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return {
                results: response.data,
                pagination,
            };
        },
        queryKey: ["reports", filters, selectedSort?.value, page],
        keepPreviousData: true,
    });

    const paginationProps: INumberedPagerProps = {
        totalResults: reports.data?.pagination?.total,
        currentPage: reports.data?.pagination?.currentPage,
        pageLimit: reports.data?.pagination?.limit,
        hasMorePages: reports.data?.pagination?.total ? reports.data?.pagination?.total >= 10000 : false,
    };

    return (
        <>
            <DocumentTitle title={t("Reports")} />
            <ModerationAdminLayout
                preTitle={<DisabledBanner />}
                title={t("Reports")}
                rightPanel={
                    <ReportFilters
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
                            showNextButton={false}
                            onChange={setPage}
                            {...paginationProps}
                        />
                    </>
                }
                content={
                    <>
                        <section className={cmdClasses.content}>
                            {reports.isLoading && (
                                <div>
                                    <Loader />
                                </div>
                            )}
                            {reports.isSuccess && reports.data.results.length === 0 && (
                                <EmptyState subtext={t("Reports matching your filters will appear here")} />
                            )}
                            {reports.isSuccess && (
                                <div className={cmdClasses.list}>
                                    {reports.data.results.map((report: IReport) => {
                                        return <ReportListItem key={report.reportID} report={report} />;
                                    })}
                                </div>
                            )}
                        </section>
                    </>
                }
            />
        </>
    );
}

export default ReportsPage;
