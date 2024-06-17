/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { ITriageRecord } from "@dashboard/moderation/CommunityManagementTypes";
import { TriageListItem } from "@dashboard/moderation/components/TriageListItem";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { AttachmentIntegrationsContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { ITriageFilters, TriageFilters } from "@dashboard/moderation/components/TriageFilters";
import { useState } from "react";
import { TriageInternalStatus } from "@dashboard/moderation/components/TriageFilters.constants";
import { ISort, Sort } from "@library/sort/Sort";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { useQueryStringSync } from "@library/routing/QueryString";
import Loader from "@library/loaders/Loader";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { EscalateModal } from "@dashboard/moderation/components/EscalateModal";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";

interface IProps {}

const filterDefaults: ITriageFilters & ISort = {
    recordInternalStatusID: [TriageInternalStatus.UNRESOLVED],
    recordUserID: [],
    recordUserRoleID: [],
    placeRecordID: [],
    sort: "-recordDateInserted",
};

interface ITriageData {
    results: ITriageRecord[];
    pagination: ILinkPages;
    sort: string;
}

const sortOptions = [
    { value: "-recordDateInserted", name: t("Newest Post") },
    { value: "recordDateInserted", name: t("Oldest Post") },
];

function TriagePage(props: IProps) {
    const cmdClasses = communityManagementPageClasses();
    const [recordToEscalate, setRecordToEscalate] = useState<ITriageRecord | null>(null);

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    const [selectedSort, setSelectedSort] = useState<ISelectBoxItem>();

    const initialStatusIDs = useQueryParam("recordInternalStatusID", filterDefaults.recordInternalStatusID);
    const initialRecordUserID = useQueryParam("recordUserID", filterDefaults.recordUserID);
    const initialRecordUserRoleID = useQueryParam("recordUserRoleID", filterDefaults.recordUserRoleID);
    const initialPlaceRecordID = useQueryParam("placeRecordID", filterDefaults.placeRecordID);

    const [filters, setFilters] = useState<ITriageFilters>({
        recordInternalStatusID: initialStatusIDs,
        recordUserID: initialRecordUserID,
        recordUserRoleID: initialRecordUserRoleID,
        placeRecordID: initialPlaceRecordID,
    });

    const handleFilterSet = (newFilters: ITriageFilters) => {
        const tmpFilters = { ...filters, ...newFilters };
        if (tmpFilters?.placeRecordID?.length > 0) {
            tmpFilters.placeRecordType = "category";
        }
        if (!tmpFilters?.placeRecordID || tmpFilters?.placeRecordID?.length === 0) {
            tmpFilters.placeRecordType = undefined;
        }
        setFilters(tmpFilters);
    };

    const [page, setPage] = useState(useQueryParamPage());

    useQueryStringSync({ ...filters, page, sort: selectedSort?.value }, { ...filterDefaults, page: 1 });

    const triageQuery = useQuery<any, IError, ITriageData>({
        queryFn: async () => {
            const response = await apiv2.get("/reports/triage", {
                params: { ...filters, expand: "users", sort: selectedSort?.value, limit: 100, page },
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
        <AdminLayout
            title={t("Triage Dashboard")}
            leftPanel={!isCompact && <ModerationNav />}
            rightPanel={<TriageFilters value={filters} onFilter={(newFilters) => handleFilterSet(newFilters)} />}
            content={
                <AttachmentIntegrationsContextProvider>
                    <section className={cmdClasses.secondaryTitleBar}>
                        <span>
                            <Sort
                                sortOptions={sortOptions}
                                selectedSort={selectedSort}
                                onChange={(sort) => {
                                    setSelectedSort(sort);
                                    if (page !== 1) setPage(1);
                                }}
                            />
                        </span>
                        <NumberedPager
                            className={cmdClasses.pager}
                            isMobile={false}
                            showNextButton={false}
                            onChange={setPage}
                            {...paginationProps}
                        />
                    </section>
                    <section className={cmdClasses.content}>
                        {triageQuery.isLoading && (
                            <div>
                                <Loader />
                            </div>
                        )}
                        {triageQuery.isSuccess && triageQuery.data.results.length === 0 && (
                            <div>
                                <p>{t("All reports are handled! 😀")}</p>
                            </div>
                        )}
                        {triageQuery.isSuccess && (
                            <div className={cmdClasses.list}>
                                {triageQuery.data.results.map((triageItem: ITriageRecord) => (
                                    <TriageListItem
                                        key={triageItem.recordID}
                                        triageItem={triageItem}
                                        onEscalate={(report) => {
                                            setRecordToEscalate(report);
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                    <EscalateModal
                        report={null}
                        recordID={recordToEscalate?.recordID ?? null}
                        recordType={recordToEscalate?.recordType ?? null}
                        isVisible={!!recordToEscalate}
                        onClose={() => {
                            setRecordToEscalate(null);
                        }}
                    />
                </AttachmentIntegrationsContextProvider>
            }
        />
    );
}

export default TriagePage;
