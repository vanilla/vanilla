/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { IAutomationRule } from "@dashboard/automationRules/AutomationRules.types";
import { AutomationRulesPreviewModal } from "@dashboard/automationRules/preview/AutomationRulesPreviewModal";
import { useUpdateRecipeStatus } from "@dashboard/automationRules/AutomationRules.hooks";
import { useToast } from "@library/features/toaster/ToastContext";
import { t } from "@vanilla/i18n";

interface IProps extends React.ComponentProps<typeof AutomationRulesPreviewModal> {
    status: IAutomationRule["status"];
    automationRuleID: IAutomationRule["automationRuleID"];
    onPreviewModalVisible: (isVisible: boolean) => void;
    labelID: string;
}

export function AutomationRulesStatusToggle(props: IProps) {
    const { automationRuleID, status, formValues, isRuleRunning, onPreviewModalVisible } = props;
    const [isPreviewVisible, setIsPreviewVisible] = useState(false);
    const [enabled, setEnabled] = useState(status === "active");
    const toast = useToast();

    useEffect(() => {
        setEnabled(status === "active");
    }, [status]);

    useEffect(() => {
        onPreviewModalVisible(isPreviewVisible);
    }, [isPreviewVisible]);

    const { mutateAsync: updateRecipeStatus } = useUpdateRecipeStatus(automationRuleID);

    const handleStatusChange = async (newStatus?: IAutomationRule["status"]) => {
        const successText = (
            <>
                {enabled ? t("Auto-run disabled.") : t("Auto-run enabled.")}
                {isRuleRunning && ` ${t("Rule status will apply once current run completes")}`}
            </>
        );

        try {
            await updateRecipeStatus({
                status: newStatus ? newStatus : enabled ? "inactive" : "active",
            });
            setIsPreviewVisible(false);
            setEnabled(!enabled);
            toast.addToast({
                autoDismiss: true,
                body: successText,
            });
        } catch (error) {
            // in the preview modal or in the toast
            if (enabled) {
                toast.addToast({
                    dismissible: true,
                    body: <>{t("Failed to disable the rule")}</>,
                });
            } else {
                throw error;
            }
        }
    };

    return (
        <>
            <DashboardToggle
                onChange={() => {
                    !enabled ? setIsPreviewVisible(true) : void handleStatusChange();
                }}
                enabled={enabled}
                labelID={props.labelID}
            />
            <AutomationRulesPreviewModal
                formValues={formValues}
                isRuleRunning={isRuleRunning}
                isVisible={isPreviewVisible}
                onConfirm={handleStatusChange}
                onCancel={() => setIsPreviewVisible(false)}
                fromStatusToggle
            />
        </>
    );
}
