/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AutomationRulesProvider, useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { t } from "@vanilla/i18n";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import { AutomationRulesHistoryTable } from "@dashboard/automationRules/history/AutomationRulesHistoryTable";
import { useAutomationRulesDispatches } from "@dashboard/automationRules/AutomationRules.hooks";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import ErrorMessages from "@library/forms/ErrorMessages";
import AutomationRulesHistoryFilter from "@dashboard/automationRules/history/AutomationRulesHistoryFilter";
import { IGetAutomationRuleDispatchesParams } from "@dashboard/automationRules/AutomationRules.types";
import { useMemo, useState } from "react";
import qs from "qs";
import QueryString from "@library/routing/QueryString";
import { dateStringInUrlToDateRange } from "@library/search/SearchUtils";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import SmartLink from "@library/routing/links/SmartLink";

export function AutomationRulesHistoryImpl() {
    const { automationRulesCatalog } = useAutomationRules();

    const classes = automationRulesClasses();

    const { search: browserQuery } = location;
    const queryFromUrl: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });

    const [query, setQuery] = useState<IGetAutomationRuleDispatchesParams>({
        limit: 30,
        page: 1,
        ...queryFromUrl,
    });
    const { dispatches, countDispatches, currentPage, isFetching, error } = useAutomationRulesDispatches(query);

    const updateQuery = (newParams: IGetAutomationRuleDispatchesParams) => {
        setQuery({
            ...query,
            ...newParams,
        });
    };

    const header = useMemo(() => {
        const allRulesHeader = <DashboardHeaderBlock title={t("Automation Rules History")} />;
        if (query.automationRuleID) {
            const ruleName = dispatches?.find(
                (dispatch) => dispatch.automationRule.automationRuleID == query.automationRuleID,
            )?.automationRule.name;

            return ruleName ? (
                <DashboardHeaderBlock title={`${t("Rule History")}: ${ruleName}`} />
            ) : isFetching ? (
                <DashboardHeaderBlock
                    title={""}
                    actionButtons={
                        <LoadingRectangle
                            className={cx(classes.padded(), classes.verticalGap)}
                            style={{ marginRight: "auto", width: 300, height: 16 }}
                        />
                    }
                />
            ) : (
                allRulesHeader
            );
        }

        return allRulesHeader;
    }, [dispatches, query.automationRuleID]);

    return (
        <>
            <QueryString
                value={{
                    page: query.page,
                    actionType: query.actionType,
                    dateUpdated: query.dateUpdated,
                    dateFinished: query.dateFinished,
                    dispatchStatus: query.dispatchStatus,
                    automationRuleID: query.automationRuleID,
                    automationRuleDispatchUUID: query.automationRuleDispatchUUID,
                }}
            />
            <div className={classes.headerContainer}>
                {header}
                <section>
                    <div className={cx(classes.flexContainer(), classes.leftGap())}>
                        <AutomationRulesHistoryFilter
                            automationRulesCatalog={automationRulesCatalog}
                            updateQuery={updateQuery}
                            filter={{
                                actionType: query.actionType,
                                dateUpdated: dateStringInUrlToDateRange(query.dateUpdated ?? ""),
                                dateFinished: dateStringInUrlToDateRange(query.dateFinished ?? ""),
                                dispatchStatus: query.dispatchStatus,
                                automationRuleID: query.automationRuleID,
                                automationRuleDispatchUUID: query.automationRuleDispatchUUID,
                            }}
                        />

                        {dispatches.length > 0 && (
                            <NumberedPager
                                {...{
                                    currentPage: parseInt(currentPage ?? "1"),
                                    totalResults: parseInt(countDispatches ?? `${dispatches.length}`),
                                    pageLimit: 30,
                                    showNextButton: false,
                                }}
                                onChange={(page: number) => updateQuery({ ...query, page: page })}
                                isMobile={false}
                            />
                        )}
                    </div>
                </section>
            </div>
            <section>
                <div className={cx(dashboardClasses().extendRow, classes.scrollTable)}>
                    {error && (
                        <div className={classes.padded()}>
                            <Message
                                type="error"
                                stringContents={error.message}
                                icon={<ErrorIcon />}
                                contents={<ErrorMessages errors={[error]} />}
                            />
                        </div>
                    )}
                    {!error && (
                        <AutomationRulesHistoryTable
                            dispatches={dispatches}
                            isLoading={isFetching}
                            updateQuery={updateQuery}
                            isFilteredByRuleID={!!query.automationRuleID}
                        />
                    )}
                </div>
            </section>
            <DashboardHelpAsset>
                <h3>{t("ABOUT HISTORY OF AUTOMATION RULES")}</h3>
                <p>{t("This history contains the past 90 days of Automation Rules that have been run.")}</p>
                <SmartLink to={"https://success.vanillaforums.com/kb/articles/1572-automation-rules-history"}>
                    {t("See documentation for more information.")}
                </SmartLink>
            </DashboardHelpAsset>
        </>
    );
}

export default function AutomationRulesHistory() {
    return (
        <AutomationRulesProvider>
            <ErrorPageBoundary>
                <AutomationRulesHistoryImpl />
            </ErrorPageBoundary>
        </AutomationRulesProvider>
    );
}
