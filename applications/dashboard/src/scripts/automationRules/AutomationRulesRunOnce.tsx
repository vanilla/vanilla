/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { AutomationRuleFormValues, IAutomationRule } from "@dashboard/automationRules/AutomationRules.types";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Message from "@library/messages/Message";
import { useRunAutomationRule } from "@dashboard/automationRules/AutomationRules.hooks";
import { useToast } from "@library/features/toaster/ToastContext";
import { AutomationRulesPreview } from "@dashboard/automationRules/preview/AutomationRulesPreview";
import ModalConfirm from "@library/modal/ModalConfirm";
import { JsonSchema } from "packages/vanilla-json-schema-forms/src";

interface IProps {
    automationRuleID: IAutomationRule["automationRuleID"];
    formValues: AutomationRuleFormValues;
    formFieldsChanged?: boolean;
    onConfirmSaveChanges(): Promise<IAutomationRule>;
    onError: (err) => void;
    isRunning?: boolean;
    schema?: JsonSchema;
}

export function AutomationRulesRunOnce(props: IProps) {
    const classes = automationRulesClasses();
    const [isPreviewModalVisible, setIsPreviewModalVisible] = useState(false);
    const [isSaveChangesModalVisible, setIsSaveChangesModalVisible] = useState(false);

    const toast = useToast();

    const { mutateAsync: runRule, isLoading, error } = useRunAutomationRule(props.automationRuleID);

    const handleSaveChanges = async () => {
        try {
            await props.onConfirmSaveChanges();
            setIsSaveChangesModalVisible(false);
            setIsPreviewModalVisible(true);
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Automation rule successfully updated.")}</>,
            });
            props.onError({});
        } catch (error) {
            props.onError(error);
        }
    };

    const handleRunOnce = async () => {
        try {
            await runRule();
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Rule run successfully triggered.")}</>,
            });
        } catch (error) {
            toast.addToast({
                dismissible: true,
                body: <Message type="error" stringContents={error.response?.data?.message ?? ""} />,
            });
        }
    };

    return (
        <>
            <DropDownItemButton
                onClick={() =>
                    props.formFieldsChanged ? setIsSaveChangesModalVisible(true) : setIsPreviewModalVisible(true)
                }
                disabled={isLoading || props.isRunning}
            >
                {t("Run Once")}
            </DropDownItemButton>
            <AutomationRulesPreview
                formValues={props.formValues}
                isRuleRunning={props.isRunning}
                isVisible={isPreviewModalVisible}
                onConfirm={handleRunOnce}
                onCancel={() => setIsPreviewModalVisible(false)}
                schema={props.schema}
            />
            <ModalConfirm
                isVisible={isSaveChangesModalVisible}
                title={t("Unsaved Changes")}
                onCancel={() => {
                    setIsSaveChangesModalVisible(false);
                }}
                onConfirm={handleSaveChanges}
                confirmTitle={t("Save & Continue")}
                bodyClassName={classes.leftAlign}
            >
                {t("Save changes first?")}
            </ModalConfirm>
        </>
    );
}
