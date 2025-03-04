/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { t } from "@vanilla/i18n";
import { cx } from "@emotion/css";
import { useRef, useState } from "react";
import { useMeasure } from "@vanilla/react-utils";
import { useSpring } from "react-spring";
import { animated } from "react-spring";
import { tableClasses } from "@dashboard/components/Table.styles";
import { automationRulesHistoryClasses } from "./AutomationRulesHistory.classes";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { Icon } from "@vanilla/icons";
import { useToast } from "@library/features/toaster/ToastContext";
import { formatUrl } from "@library/utility/appUtils";
import {
    AutomationRuleDispatchStatusType,
    IAutomationRuleDispatch,
    IAutomationRulesCatalog,
    IGetAutomationRuleDispatchesParams,
} from "@dashboard/automationRules/AutomationRules.types";
import { loadingPlaceholder, mapApiValuesToFormValues } from "@dashboard/automationRules/AutomationRules.utils";
import DateTime, { DateFormats } from "@library/content/DateTime";
import AutomationRulesSummary from "@dashboard/automationRules/AutomationRulesSummary";
import ProfileLink from "@library/navigation/ProfileLink";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { iconClasses } from "@library/icons/iconStyles";
import { ErrorIcon } from "@library/icons/common";
import { TableAccordion } from "@dashboard/components/TableAccordion";
import SmartLink from "@library/routing/links/SmartLink";
import { unknownUserFragment } from "@library/features/users/constants/userFragment";

interface IProps {
    dispatches?: IAutomationRuleDispatch[];
    isLoading?: boolean;
    updateQuery: (newFilter: IGetAutomationRuleDispatchesParams) => void;
    isFilteredByRuleID?: boolean;
    automationRulesCatalog?: IAutomationRulesCatalog;
}

export function AutomationRulesHistoryTable(props: IProps) {
    const { dispatches, isLoading, updateQuery, isFilteredByRuleID, automationRulesCatalog } = props;
    const classes = automationRulesHistoryClasses();

    return (
        <table className={classes.table}>
            <thead className={classes.tableHeader}>
                <tr className={cx(tableClasses().row)}>
                    {[isFilteredByRuleID ? "Action" : "Rule", "Affected", "Updated", "Status", "Last run", ""].map(
                        (header, index) => (
                            <th
                                key={index}
                                className={cx(tableClasses().head, { [classes.tableFirstHeader]: header === "Rule" })}
                            >
                                {header}
                            </th>
                        ),
                    )}
                </tr>
            </thead>
            <tbody>
                {isLoading && loadingPlaceholder("history")}
                {dispatches &&
                    !isLoading &&
                    dispatches.map((dispatch, index) => (
                        <AutomationRulesHistoryAccordion
                            dispatch={dispatch}
                            key={index}
                            updateQuery={updateQuery}
                            isFilteredByRuleID={isFilteredByRuleID}
                            automationRulesCatalog={automationRulesCatalog}
                        />
                    ))}
            </tbody>
        </table>
    );
}

function AutomationRulesHistoryAccordion(props: {
    dispatch: IAutomationRuleDispatch;
    updateQuery: (newFilter: IGetAutomationRuleDispatchesParams) => void;
    isFilteredByRuleID?: boolean;
    automationRulesCatalog?: IAutomationRulesCatalog;
}) {
    const { dispatch, updateQuery, isFilteredByRuleID, automationRulesCatalog } = props;
    const classes = automationRulesHistoryClasses();

    const triggerContentType = automationRulesCatalog?.triggers?.[dispatch.trigger.triggerType ?? ""]?.contentType;

    const [isExpanded, setIsExpanded] = useState(false);

    const toast = useToast();

    const dateLastRunRef = useRef<HTMLDivElement>(null);
    const { height: dateLastRunHeight } = useMeasure(dateLastRunRef);

    const { height: animatedDateLastRunHeight } = useSpring({
        height: isExpanded ? dateLastRunHeight : 0,
    });

    async function copyUrl() {
        const fullUrl = formatUrl(
            `/settings/automation-rules/history?automationRuleID=${dispatch.automationRule.automationRuleID}&automationRuleDispatchUUID=${dispatch.automationRuleDispatchUUID}`,
            true,
        );
        await navigator.clipboard.writeText(fullUrl);
    }

    return (
        <tr className={cx(tableClasses().row)}>
            <td className={tableClasses().cell}>
                <span className={classes.tableCellWrapper}>
                    <TableAccordion
                        onExpandChange={setIsExpanded}
                        toggleButtonContent={
                            <div>{isFilteredByRuleID ? dispatch.action.actionName : dispatch.automationRule.name}</div>
                        }
                    >
                        <AutomationRulesSummary
                            formValues={{
                                ...mapApiValuesToFormValues(dispatch),
                                ...(isFilteredByRuleID && { action: undefined }),
                            }}
                        />
                    </TableAccordion>
                </span>
            </td>
            <td className={tableClasses().cell}>
                <SmartLink
                    to={`/log/automationrules/${dispatch.automationRuleDispatchUUID}`}
                    className={classes.tableCellWrapper}
                >
                    <span>
                        {triggerContentType === "users"
                            ? `${t("Users")}: ${dispatch.affectedRows?.user ?? ""}`
                            : `${t("Posts")}: ${dispatch.affectedRows?.post ?? ""}`}
                    </span>
                </SmartLink>
            </td>
            <td className={tableClasses().cell}>
                <span className={cx(classes.tableCellWrapper, classes.tableDateCell)}>
                    <div className={automationRulesClasses().flexContainer()}>
                        <div>
                            <DateTime timestamp={dispatch.automationRule.dateUpdated} type={DateFormats.EXTENDED} />
                        </div>
                    </div>
                    <animated.div style={{ height: animatedDateLastRunHeight }}>
                        <div>
                            <span>{`${t("by")} `}</span>
                            <ProfileLink userFragment={dispatch.automationRule.updateUser ?? unknownUserFragment()} />
                        </div>
                    </animated.div>
                </span>
            </td>
            <td className={cx(tableClasses().cell, classes.centerAlign)}>
                <span>
                    <AutomationRulesHistoryStatus
                        dispatchID={dispatch.automationRuleDispatchUUID}
                        status={dispatch.dispatchStatus}
                    />
                </span>
            </td>
            <td className={tableClasses().cell}>
                <span className={cx(classes.tableCellWrapper, classes.tableDateCell)}>
                    <div className={automationRulesClasses().flexContainer()}>
                        <DateTime
                            timestamp={dispatch.dateFinished ?? dispatch.dateDispatched}
                            type={DateFormats.EXTENDED}
                        />
                    </div>
                    <animated.div style={{ height: animatedDateLastRunHeight }}>
                        <div ref={dateLastRunRef}>
                            {dispatch.dispatchType === "triggered" && <div>{t("Auto Run")}</div>}
                            {(dispatch.dispatchType === "manual" || dispatch.dispatchType === "initial") && (
                                <div>
                                    <span>{`${t("by")} `}</span>
                                    <ProfileLink
                                        userFragment={{
                                            userID: dispatch.dispatchUser.userID,
                                            name: dispatch.dispatchUser.name,
                                        }}
                                    />
                                </div>
                            )}
                        </div>
                    </animated.div>
                </span>
            </td>
            <td className={tableClasses().cell}>
                <span>
                    <div className={automationRulesClasses().flexContainer()}>
                        <ToolTip label={t("Copy Link to Single Run View")}>
                            <Button
                                title={t("Copy Link")}
                                aria-label={t("Copy Link")}
                                buttonType={ButtonTypes.ICON_COMPACT}
                                onClick={async () => {
                                    await copyUrl();
                                    toast.addToast({
                                        body: <>{t("Link copied to clipboard.")}</>,
                                        autoDismiss: true,
                                    });
                                }}
                            >
                                <Icon icon={"copy-link"} />
                            </Button>
                        </ToolTip>
                        <ToolTip label={t("View Run History for This Rule")}>
                            <Button
                                onClick={() =>
                                    updateQuery({ automationRuleID: dispatch.automationRule.automationRuleID })
                                }
                                ariaLabel={t("History By Rule")}
                                buttonType={ButtonTypes.ICON_COMPACT}
                            >
                                <Icon icon={"meta-time"} />
                            </Button>
                        </ToolTip>
                    </div>
                </span>
            </td>
        </tr>
    );
}

function AutomationRulesHistoryStatus(props: {
    status: AutomationRuleDispatchStatusType;
    dispatchID: IAutomationRuleDispatch["automationRuleDispatchUUID"];
}) {
    switch (props.status) {
        case "success":
            return (
                <ToolTip label={t("Rule was run successfully.")}>
                    <ToolTipIcon>
                        <Icon icon={"status-success"} size="compact" className={iconClasses().successFgColor} />
                    </ToolTipIcon>
                </ToolTip>
            );
        case "queued":
        case "running":
            return (
                <ToolTip label={t("Rule is running.")}>
                    <ToolTipIcon>
                        <Icon icon={"status-running"} className={iconClasses().successFgColor} />
                    </ToolTipIcon>
                </ToolTip>
            );
        case "failed":
            return (
                <ToolTip label={`${t("Something went wrong with this run.")} ${t("Run ID")}: ${props.dispatchID}`}>
                    <ToolTipIcon>
                        <ErrorIcon className={cx(iconClasses().errorFgColor, automationRulesClasses().leftGap(4))} />
                    </ToolTipIcon>
                </ToolTip>
            );
        case "warning":
            return (
                <ToolTip
                    label={`${t("Something went wrong. Some of this rule was run.")} ${t("Run ID")}: ${
                        props.dispatchID
                    }`}
                >
                    <ToolTipIcon>
                        <Icon
                            icon={"status-warning"}
                            size="compact"
                            className={cx(iconClasses().errorFgColor, automationRulesClasses().leftGap(4))}
                        />
                    </ToolTipIcon>
                </ToolTip>
            );
        default:
            return <span>{t("Unknown")}</span>;
    }
}
