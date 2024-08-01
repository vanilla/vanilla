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

export function AutomationRulesActions(props: IAutomationRule) {
    const classes = automationRulesClasses();

    const dispatchStatusIsPending =
        props.recentDispatch?.dispatchStatus === "queued" || props.recentDispatch?.dispatchStatus === "running";

    return (
        <div className={cx(classes.flexContainer(), classes.noOverflow)}>
            <LinkAsButton
                to={`/settings/automation-rules/history?automationRuleID=${props.automationRuleID}`}
                ariaLabel={t("History")}
                buttonType={ButtonTypes.ICON_COMPACT}
            >
                <Icon icon={"meta-time"} />
            </LinkAsButton>
            <LinkAsButton
                to={`/settings/automation-rules/${props.automationRuleID}/edit`}
                ariaLabel={t("Edit")}
                buttonType={ButtonTypes.ICON_COMPACT}
            >
                <EditIcon />
            </LinkAsButton>
            <ConditionalWrap
                component={ToolTip}
                condition={dispatchStatusIsPending}
                componentProps={{ label: t("Rule may not be deleted while it is running") }}
            >
                <span>
                    <AutomationRulesDeleteRule asActionButtonInTable {...props} />
                </span>
            </ConditionalWrap>
        </div>
    );
}
