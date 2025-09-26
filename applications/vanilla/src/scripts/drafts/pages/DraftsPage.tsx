/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import TitleBar from "@library/headers/TitleBar";
import { Backgrounds } from "@library/layout/Backgrounds";
import { SectionProvider, useSection } from "@library/layout/LayoutContext";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import DocumentTitle from "@library/routing/DocumentTitle";
import { t } from "@vanilla/i18n";
import "@library/theming/reset";
import Container from "@library/layout/components/Container";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import PanelWidget from "@library/layout/components/PanelWidget";
import { useState } from "react";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { Tabs } from "@library/sectioning/Tabs";
import { DraftList } from "@vanilla/addon-vanilla/drafts/components/DraftList";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { useQueryStringSync } from "@library/routing/QueryString";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { DraftsSortValue, DraftStatus } from "@vanilla/addon-vanilla/drafts/types";
import { Sort } from "@library/sort/Sort";
import { useDraftListQuery } from "@vanilla/addon-vanilla/drafts/Draft.hooks";
import { ResultPaginationInfo } from "@library/result/ResultPaginationInfo";
import { SearchPagination } from "@library/search/SearchPagination";
import { DraftsFilter } from "@vanilla/addon-vanilla/drafts/components/DraftsFilter";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import ErrorMessages from "@library/forms/ErrorMessages";
import { SearchPageResultsLoader } from "@library/search/SearchPageResultsLoader";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { Row } from "@library/layout/Row";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { draftsClasses } from "@vanilla/addon-vanilla/drafts/Drafts.classes";

export enum DraftsPageTab {
    DRAFTS = "",
    SCHEDULE = "schedule",
    ERRORS = "errors",
}

const sortOptionsByValue = {
    dateScheduled: { value: "dateScheduled", name: t("Next to Publish") },
    "-dateScheduled": { value: "-dateScheduled", name: t("Last to Publish") },
};

export default function DraftsPage() {
    const tabFromUrl = useQueryParam("tab", DraftsPageTab.DRAFTS);
    const sortFromUrl = useQueryParam("sort", undefined);
    const dateUpdatedFilter = useQueryParam("dateUpdated", undefined);
    const dateScheduledFilter = useQueryParam("dateScheduled", undefined);
    const recordTypeFilter = useQueryParam("recordType", undefined);

    const [currentTab, setCurrentTab] = useState<DraftsPageTab>(tabFromUrl);

    const { hasPermission } = usePermissionsContext();
    const canSchedule = hasPermission("schedule.allow");

    const isSchedule = canSchedule && currentTab !== DraftsPageTab.DRAFTS;

    const [draftsQuery, setDraftsQuery] = useState<DraftsApi.GetParams>({
        limit: 30,
        expand: true,
        draftStatus: canSchedule ? draftTabToQueryParam(currentTab) : undefined,
        sort: isSchedule ? sortFromUrl ?? "dateScheduled" : undefined,
        page: useQueryParamPage(),
        dateUpdated: dateUpdatedFilter ?? undefined,
        dateScheduled: dateScheduledFilter ?? undefined,
        recordType: recordTypeFilter ?? undefined,
    });

    useQueryStringSync(
        {
            tab: currentTab,
            sort: draftsQuery.sort,
            page: draftsQuery.page,
            dateUpdated: draftsQuery.dateUpdated,
            dateScheduled: draftsQuery.dateScheduled,
            recordType: draftsQuery.recordType,
        },
        { tab: "" },
    );
    const isCompact = !useSection().isFullWidth;

    const { data: result, isLoading, error } = useDraftListQuery(draftsQuery);

    const draftList = (
        <>
            {error && (
                <Message
                    type="error"
                    stringContents={error.message}
                    icon={<ErrorIcon />}
                    contents={<ErrorMessages errors={[error]} />}
                />
            )}
            {isLoading && <SearchPageResultsLoader count={10} />}
            {result?.data && <DraftList currentTab={currentTab} drafts={result?.data} />}
        </>
    );

    const header = isLoading ? (
        <LoadingRectangle height={24} width={240} />
    ) : (
        <Row align="center" gap={32}>
            {isSchedule && (
                <Sort
                    sortOptions={Object.values(sortOptionsByValue)}
                    selectedSort={sortOptionsByValue[draftsQuery.sort ?? ""]}
                    onChange={(newSort) =>
                        setDraftsQuery((prev) => ({
                            ...prev,
                            sort: newSort?.value as DraftsSortValue,
                        }))
                    }
                />
            )}
            {isCompact && (
                <DraftsFilter
                    inModal
                    draftsQuery={draftsQuery}
                    isSchedule={isSchedule}
                    onFilter={(filterValues) => {
                        setDraftsQuery({ ...draftsQuery, ...filterValues });
                    }}
                />
            )}
            <ResultPaginationInfo pages={result?.paging} alignRight />
        </Row>
    );

    return (
        <SectionProvider type={SectionTypes.TWO_COLUMNS}>
            <Backgrounds />
            <DocumentTitle title={canSchedule ? t("Drafts and Schedule") : t("Drafts")}>
                <TitleBar />
                <Container>
                    <SectionTwoColumns
                        className="hasLargePadding"
                        mainTop={
                            <>
                                <PanelWidget>
                                    <HomeWidgetContainer
                                        title={
                                            canSchedule ? t("Manage Drafts and Scheduled Content") : t("Manage Drafts")
                                        }
                                    >
                                        {canSchedule ? (
                                            <Tabs
                                                extendContainer
                                                includeBorder={false}
                                                largeTabs
                                                activeTab={
                                                    currentTab === DraftsPageTab.SCHEDULE
                                                        ? 1
                                                        : currentTab === DraftsPageTab.ERRORS
                                                        ? 2
                                                        : 0
                                                }
                                                tabType={TabsTypes.BROWSE}
                                                data={[
                                                    {
                                                        tabID: DraftsPageTab.DRAFTS,
                                                        label: "Drafts",
                                                        contents: draftList,
                                                    },
                                                    {
                                                        tabID: DraftsPageTab.SCHEDULE,
                                                        label: "Scheduled Content",
                                                        contents: draftList,
                                                    },
                                                    {
                                                        tabID: DraftsPageTab.ERRORS,
                                                        label: "Errors",
                                                        contents: draftList,
                                                    },
                                                ]}
                                                onChange={({ tabID }) => {
                                                    setCurrentTab(tabID as DraftsPageTab);
                                                    setDraftsQuery((prev) => {
                                                        return {
                                                            ...prev,
                                                            draftStatus: draftTabToQueryParam(tabID as DraftsPageTab),
                                                            sort:
                                                                tabID !== DraftsPageTab.DRAFTS
                                                                    ? sortFromUrl ?? "dateScheduled"
                                                                    : undefined,
                                                            page: undefined,
                                                        };
                                                    });
                                                }}
                                                extraButtons={header}
                                            />
                                        ) : (
                                            <>
                                                <div className={draftsClasses().onlyDraftsHeader}>{header}</div>
                                                <div>{draftList}</div>
                                            </>
                                        )}
                                        <SearchPagination
                                            onNextClick={
                                                result?.paging.next
                                                    ? () => {
                                                          setDraftsQuery((prev) => ({
                                                              ...prev,
                                                              page: result?.paging?.next,
                                                          }));
                                                      }
                                                    : undefined
                                            }
                                            onPreviousClick={
                                                (draftsQuery.page ?? 0) > 1
                                                    ? () =>
                                                          setDraftsQuery((prev) => ({
                                                              ...prev,
                                                              page: result?.paging?.prev,
                                                          }))
                                                    : undefined
                                            }
                                        />
                                    </HomeWidgetContainer>
                                </PanelWidget>
                            </>
                        }
                        secondaryTop={
                            !isCompact && (
                                <PanelWidget>
                                    <DraftsFilter
                                        draftsQuery={draftsQuery}
                                        isSchedule={isSchedule}
                                        onFilter={(filterValues) => {
                                            setDraftsQuery({ ...draftsQuery, ...filterValues });
                                        }}
                                    />
                                </PanelWidget>
                            )
                        }
                    />
                </Container>
            </DocumentTitle>
        </SectionProvider>
    );
}

function draftTabToQueryParam(currentTab: DraftsPageTab) {
    switch (currentTab) {
        case DraftsPageTab.SCHEDULE:
            return DraftStatus.SCHEDULED;
        case DraftsPageTab.ERRORS:
            return DraftStatus.ERROR;
        default:
            return DraftStatus.DRAFT;
    }
}
