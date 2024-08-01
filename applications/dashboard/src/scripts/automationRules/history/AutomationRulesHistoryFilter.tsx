/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useMemo } from "react";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { t } from "@vanilla/i18n";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import {
    AutomationRuleDispatchStatusType,
    IAutomationRulesCatalog,
    IAutomationRulesHistoryFilter,
    IGetAutomationRuleDispatchesParams,
} from "@dashboard/automationRules/AutomationRules.types";
import Button from "@library/forms/Button";
import { ClearIcon } from "@vanilla/ui/src/forms/shared/ClearIcon";
import AutomationRulesHistoryDateFilter from "@dashboard/automationRules/history/AutomationRulesHistoryDateFilter";
import { cx } from "@emotion/css";
import { ToolTip } from "@library/toolTip/ToolTip";
import { automationRulesHistoryClasses } from "@dashboard/automationRules/history/AutomationRulesHistory.classes";

interface IProps {
    filter: IAutomationRulesHistoryFilter;
    updateQuery: (newFilter: IGetAutomationRuleDispatchesParams) => void;
    className?: string;
    automationRulesCatalog?: IAutomationRulesCatalog;
}

export default function AutomationRulesHistoryFilter(props: IProps) {
    const { filter, updateQuery, automationRulesCatalog } = props;

    const classes = automationRulesClasses();

    const hasFilters = Object.values(filter).some((value) =>
        value !== undefined && typeof value === "object" ? Object.values(value).some((v) => v !== undefined) : value,
    );

    const actionTypeOptions: ISelectBoxItem[] = useMemo(() => {
        const actionTypeOptions: ISelectBoxItem[] = [{ name: t("All"), value: "all" }];
        if (automationRulesCatalog) {
            return [
                ...actionTypeOptions,
                ...Object.keys(automationRulesCatalog.actions).map((actionType) => {
                    return {
                        value: actionType,
                        name: t(automationRulesCatalog.actions[actionType].name),
                    };
                }),
            ];
        }
        return actionTypeOptions;
    }, [automationRulesCatalog]);

    const statusOptions: ISelectBoxItem[] = [
        { name: t("All"), value: "all" },
        ...["Success", "Running", "Failed"].map(
            (status) =>
                ({
                    name: t(status),
                    value: status.toLocaleLowerCase() as AutomationRuleDispatchStatusType,
                } as ISelectBoxItem),
        ),
    ];

    const actionTypeDropdownID = uniqueIDFromPrefix("automationRulesHistoryFilter_actionType");
    const statusDropdownID = uniqueIDFromPrefix("automationRulesHistoryFilter_actionType");

    return (
        <div
            className={cx(
                classes.flexContainer(true),
                classes.padded(true),
                automationRulesHistoryClasses().filterConatainer,
            )}
        >
            <div className={cx(classes.flexContainer(), classes.rightGap())}>
                <span id={actionTypeDropdownID} className={classes.noWrap}>
                    {t("Action Type")}:
                </span>
                <SelectBox
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                    options={actionTypeOptions}
                    describedBy={actionTypeDropdownID}
                    value={
                        actionTypeOptions.find((option) => option.value === filter.actionType) || {
                            name: t("All"),
                            value: "all",
                        }
                    }
                    renderLeft={false}
                    horizontalOffset={true}
                    offsetPadding={true}
                    onChange={({ value }) => {
                        updateQuery({
                            actionType:
                                value === "all" ? undefined : (value as IAutomationRulesHistoryFilter["actionType"]),
                        });
                    }}
                    className={classes.leftGap(4)}
                />
            </div>
            <AutomationRulesHistoryDateFilter filter={filter} updateQuery={updateQuery} title={t("Updated")} />
            <AutomationRulesHistoryDateFilter filter={filter} updateQuery={updateQuery} title={t("Last Run")} />
            <div className={cx(classes.flexContainer(), classes.rightGap())}>
                <span id={statusDropdownID} className={classes.noWrap}>
                    {t("Status")}:
                </span>
                <SelectBox
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                    options={statusOptions}
                    describedBy={statusDropdownID}
                    value={
                        statusOptions.find((option) => option.value === filter.dispatchStatus?.[0]) || {
                            name: t("All"),
                            value: "all",
                        }
                    }
                    renderLeft={false}
                    horizontalOffset={true}
                    offsetPadding={true}
                    onChange={({ value }) => {
                        updateQuery({
                            dispatchStatus:
                                value === "all"
                                    ? undefined
                                    : value === "running"
                                    ? ["running", "queued"]
                                    : ([value] as AutomationRuleDispatchStatusType[]),
                        });
                    }}
                    className={classes.leftGap(4)}
                />
            </div>
            {filter.automationRuleID && (
                <span className={classes.flexContainer()}>
                    <span>{`${t("Rule ID")}: ${filter.automationRuleID}`}</span>
                    <Button
                        buttonType={ButtonTypes.ICON_COMPACT}
                        onClick={() => {
                            updateQuery({
                                automationRuleID: undefined,
                            });
                        }}
                    >
                        <ClearIcon />
                    </Button>
                </span>
            )}
            {filter.automationRuleDispatchUUID && (
                <span className={classes.flexContainer()}>
                    <span>{`${t("Run ID")}: ${filter.automationRuleDispatchUUID}`}</span>
                    <Button
                        buttonType={ButtonTypes.ICON_COMPACT}
                        onClick={() => {
                            updateQuery({
                                automationRuleDispatchUUID: undefined,
                            });
                        }}
                    >
                        <ClearIcon />
                    </Button>
                </span>
            )}
            {hasFilters && (
                <ToolTip label={t("Clear all filters")}>
                    <span>
                        <Button
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => {
                                updateQuery({
                                    automationRuleDispatchUUID: undefined,
                                    page: 1,
                                    automationRuleID: undefined,
                                    dateFinished: undefined,
                                    dateUpdated: undefined,
                                    dispatchStatus: undefined,
                                    actionType: undefined,
                                });
                            }}
                        >
                            {t("Clear All")}
                        </Button>
                    </span>
                </ToolTip>
            )}
        </div>
    );
}
