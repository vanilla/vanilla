/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { EditIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import { automationRulesClasses } from "./AutomationRules.classes";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import { IAutomationRule } from "@dashboard/automationRules/AutomationRules.types";
import { AutomationRulesDeleteRule } from "@dashboard/automationRules/AutomationRulesDeleteRule";

export function AutomationRulesActions(props: { isEscalationRulesMode?: boolean; automationRule: IAutomationRule }) {
    const { automationRule, isEscalationRulesMode } = props;
    const classes = automationRulesClasses();

    const dispatchStatusIsPending =
        automationRule.recentDispatch?.dispatchStatus === "queued" ||
        automationRule.recentDispatch?.dispatchStatus === "running";

    return (
        <div className={cx(classes.flexContainer(), classes.noOverflow)}>
            <ToolTip label={t("View History")} customWidth={40}>
                <span>
                    <LinkAsButton
                        to={`/settings/automation-rules/history?automationRuleID=${automationRule.automationRuleID}`}
                        ariaLabel={t("History")}
                        buttonType={ButtonTypes.ICON_COMPACT}
                    >
                        <Icon icon={"meta-time"} />
                    </LinkAsButton>
                </span>
            </ToolTip>
            <ToolTip label={t("Edit Rule")} customWidth={40}>
                <span>
                    <LinkAsButton
                        to={
                            isEscalationRulesMode
                                ? `/dashboard/content/escalation-rules/${automationRule.automationRuleID}/edit`
                                : `/settings/automation-rules/${automationRule.automationRuleID}/edit`
                        }
                        ariaLabel={t("Edit Rule")}
                        buttonType={ButtonTypes.ICON_COMPACT}
                    >
                        <EditIcon />
                    </LinkAsButton>
                </span>
            </ToolTip>
            <ToolTip
                label={dispatchStatusIsPending ? t("Rule may not be deleted while it is running") : t("Delete Rule")}
                customWidth={40}
            >
                <span>
                    <AutomationRulesDeleteRule asActionButtonInTable {...automationRule} />
                </span>
            </ToolTip>
        </div>
    );
}
