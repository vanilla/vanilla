/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import ProfileFieldsListClasses from "@dashboard/userProfiles/components/ProfileFieldsList.classes";
import { cx } from "@emotion/css";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { useEffect, useMemo, useState } from "react";
import { ISortOption, ITableData, Table } from "@dashboard/components/Table";
import DateTime from "@library/content/DateTime";
import LinkAsButton from "@library/routing/LinkAsButton";
import { AutomationRulesSearchbar } from "@dashboard/automationRules/AutomationRulesSearchBar";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { AutomationRulesFilter } from "@dashboard/automationRules/AutomationRulesFilter";
import { useRecipes } from "@dashboard/automationRules/AutomationRules.hooks";
import { AutomationRulesProvider, useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { IAutomationRule, IAutomationRulesFilterValues } from "@dashboard/automationRules/AutomationRules.types";
import {
    loadingPlaceholder,
    mapApiValuesToFormValues,
    sortDateColumn,
} from "@dashboard/automationRules/AutomationRules.utils";
import AutomationRulesSummary from "@dashboard/automationRules/AutomationRulesSummary";
import { AutomationRulesActions } from "@dashboard/automationRules/AutomationRulesTableActions";
import ErrorMessages from "@library/forms/ErrorMessages";
import { AutomationRulesStatusToggle } from "@dashboard/automationRules/AutomationRulesStatusToggle";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { Icon } from "@vanilla/icons";
import ModalConfirm from "@library/modal/ModalConfirm";
import Button from "@library/forms/Button";
import { TableAccordion } from "@dashboard/components/TableAccordion";

export function AutomationRulesListImpl() {
    const classes = automationRulesClasses();
    const [maxLimitModalVisible, setMaxLimitModalVisible] = useState<boolean>(false);

    const RECIPES_MAX_LIMIT = 150;

    const [searchQuery, setSearchQuery] = useState<string>("");
    const [filters, setFilters] = useState<IAutomationRulesFilterValues>({});
    const [sortedColumn, setSortedColumn] = useState<ISortOption[]>();
    const [previewModalVisible, setPreviewModalVisible] = useState(false);
    const [expandedRules, setExpandedRules] = useState<Array<IAutomationRule["automationRuleID"]>>([]);

    const { initialOrderedRulesIDs, setInitialOrderedRulesIDs } = useAutomationRules();

    // as we continiously refetch the rules, we store expanded rules in state so we keep them expanded when list is updated
    const onRuleExpandChange = (ruleID: IAutomationRule["automationRuleID"], isExpanded: boolean) => {
        setExpandedRules((existingExpandedRuleIDs) => {
            return isExpanded && !existingExpandedRuleIDs.includes(ruleID)
                ? [...existingExpandedRuleIDs, ruleID]
                : existingExpandedRuleIDs.filter((id) => id !== ruleID);
        });
    };

    const { recipes, isLoading, error, isRefetching } = useRecipes(!previewModalVisible);

    // let's store initial fetch order, so when we enable/disable auto-run,
    // we can keep the same order as api response is always ordered by dateLastRun and enabled first
    useEffect(() => {
        if (recipes && !initialOrderedRulesIDs?.length && !isRefetching) {
            setInitialOrderedRulesIDs?.(recipes.map((rule) => rule.automationRuleID));
        }
    }, [recipes, isRefetching]);

    const rulesList = useMemo(() => {
        let refinedData = recipes ?? [];

        // match with initial sorted list from api
        if (initialOrderedRulesIDs?.length) {
            refinedData = refinedData.sort((a, b) => {
                return (
                    initialOrderedRulesIDs.indexOf(a.automationRuleID) -
                    initialOrderedRulesIDs.indexOf(b.automationRuleID)
                );
            });
        }

        // filter
        Object.keys(filters).forEach((filter) => {
            if (filters[filter]) {
                refinedData =
                    filter === "status" && filters[filter]
                        ? refinedData.filter((rule) => rule[filter] === filters[filter])
                        : refinedData.filter(
                              (rule) =>
                                  rule[filter].actionType === filters[filter] ||
                                  rule[filter].triggerType === filters[filter],
                          );
            }
        });

        // search
        if (searchQuery && searchQuery !== "") {
            refinedData = refinedData.filter(
                (rule) =>
                    rule.trigger.triggerName.toLocaleLowerCase().includes(searchQuery.toLocaleLowerCase()) ||
                    rule.action.actionName.toLocaleLowerCase().includes(searchQuery.toLocaleLowerCase()) ||
                    rule.name.toLocaleLowerCase().includes(searchQuery.toLocaleLowerCase()),
            );
        }

        return refinedData;
    }, [filters, searchQuery, recipes, expandedRules, initialOrderedRulesIDs]);

    const rows = useMemo(() => {
        if (isLoading) {
            return loadingPlaceholder() as ITableData[];
        }
        if (rulesList.length) {
            return rulesList.map((rule, index) => {
                return {
                    rule: (
                        <TableAccordion
                            toggleButtonContent={<span>{rule.name}</span>}
                            onExpandChange={(newVal) => onRuleExpandChange(rule.automationRuleID, newVal)}
                            isExpanded={expandedRules.includes(rule.automationRuleID)}
                        >
                            <AutomationRulesSummary formValues={mapApiValuesToFormValues(rule)} />
                        </TableAccordion>
                    ),
                    "last updated": <div>{rule.dateUpdated && <DateTime timestamp={rule.dateUpdated} />}</div>,
                    "last run": (
                        <div className={classes.spaceBetween}>
                            {rule.dateLastRun && (
                                <DateTime timestamp={rule.dateLastRun} className={classes.tableDateCell} />
                            )}
                            {rule?.recentDispatch?.dispatchStatus === "failed" && (
                                <Icon className={iconClasses().errorFgColor} icon={"status-warning"} size={"compact"} />
                            )}
                            {(rule?.recentDispatch?.dispatchStatus === "queued" ||
                                rule?.recentDispatch?.dispatchStatus === "running") && (
                                <Icon
                                    className={iconClasses().successFgColor}
                                    icon={"status-running"}
                                    size={"compact"}
                                />
                            )}
                        </div>
                    ),
                    "auto-run": (
                        <AutomationRulesStatusToggle
                            automationRuleID={rule.automationRuleID}
                            status={rule.status}
                            isRuleRunning={
                                rule?.recentDispatch?.dispatchStatus === "queued" ||
                                rule?.recentDispatch?.dispatchStatus === "running"
                            }
                            formValues={mapApiValuesToFormValues(rule)}
                            onPreviewModalVisible={setPreviewModalVisible}
                        />
                    ),
                    actions: <AutomationRulesActions {...rule} />,
                };
            });
        }
        return [
            {
                rule: "",
                "last updated": "",
                "last run": "",
                "auto-run": "",
                actions: "",
            },
        ];
    }, [rulesList]);

    return (
        <>
            <div className={classes.headerContainer}>
                <DashboardHeaderBlock
                    title={t("Automation Rules")}
                    actionButtons={
                        recipes?.length === RECIPES_MAX_LIMIT ? (
                            <Button
                                buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                                onClick={() => setMaxLimitModalVisible(true)}
                            >
                                {t("Add Rule")}
                            </Button>
                        ) : (
                            <LinkAsButton
                                style={{ marginLeft: "auto" }}
                                buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                                to={"/settings/automation-rules/add"}
                            >
                                {t("Add Rule")}
                            </LinkAsButton>
                        )
                    }
                />
                <section>
                    <div className={classes.searchAndFilterContainer}>
                        <AutomationRulesSearchbar onSearch={setSearchQuery} />
                        <AutomationRulesFilter onFilter={setFilters} filters={filters} />
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
                        <Table
                            headerClassNames={classes.tableHeader}
                            rowClassNames={ProfileFieldsListClasses().extendTableRows}
                            cellClassNames={classes.tableCell}
                            data={rows}
                            hiddenHeaders={["actions"]}
                            sortable={true}
                            columnsNotSortable={["rule", "auto-run", "actions"]}
                            tableClassNames={classes.table}
                            customSortByFnPerColumn={{
                                "last updated": sortDateColumn,
                                "last run": sortDateColumn,
                            }}
                            onSortChange={setSortedColumn}
                            initialSortBy={sortedColumn}
                        />
                    )}
                </div>
            </section>
            <DashboardHelpAsset>
                <h3>{t("Automation Rules").toLocaleUpperCase()}</h3>
                <p>{t("Create and manage automations to easily manage posts and users.")}</p>
                <SmartLink to={"https://success.vanillaforums.com/kb/articles/1569-automation-rules"}>
                    {t("See documentation for more information.")}
                </SmartLink>
            </DashboardHelpAsset>
            <ModalConfirm
                isVisible={maxLimitModalVisible}
                title={t("Maximum Limit Reached")}
                onCancel={() => setMaxLimitModalVisible(false)}
                cancelTitle={t("Close")}
            >
                {t("You cannot add more than 150 automation rules. Delete some rules and try again.")}
            </ModalConfirm>
        </>
    );
}

export default function AutomationRulesList() {
    return (
        <AutomationRulesProvider>
            <ErrorPageBoundary>
                <AutomationRulesListImpl />
            </ErrorPageBoundary>
        </AutomationRulesProvider>
    );
}
