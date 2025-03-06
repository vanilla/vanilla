/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { IAutomationRule } from "@dashboard/automationRules/AutomationRules.types";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import Message from "@library/messages/Message";
import { messagesClasses } from "@library/messages/messageStyles";
import { useDeleteRecipe } from "./AutomationRules.hooks";
import { useToast } from "@library/features/toaster/ToastContext";
import { useHistory } from "react-router";
import { ErrorIcon } from "@library/icons/common";
import { IError } from "@library/errorPages/CoreErrorMessages";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

interface IProps extends IAutomationRule {
    asActionButtonInTable?: boolean;
}

export function AutomationRulesDeleteRule(props: IProps) {
    const classes = automationRulesClasses();
    const [isConfirmDeleteVisible, setIsConfirmDeleteVisible] = useState(false);
    const [error, setError] = useState<IError>();

    const toast = useToast();
    const history = useHistory();

    const isRuleRunning =
        props.recentDispatch?.dispatchStatus === "queued" || props.recentDispatch?.dispatchStatus === "running";

    const { mutateAsync: deleteRecipe, isLoading } = useDeleteRecipe(props.automationRuleID);

    const handleDelete = async () => {
        try {
            await deleteRecipe();
            setIsConfirmDeleteVisible(false);
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Automation rule successfully deleted.")}</>,
            });
            !props.asActionButtonInTable && history.push("/settings/automation-rules");
        } catch (error) {
            setError(error.message);
        }
    };

    return (
        <>
            {props.asActionButtonInTable && (
                <Button
                    onClick={() => setIsConfirmDeleteVisible(true)}
                    ariaLabel={t("Delete")}
                    buttonType={ButtonTypes.ICON_COMPACT}
                    disabled={isRuleRunning}
                >
                    <Icon icon={"delete"} />
                </Button>
            )}
            {!props.asActionButtonInTable && (
                <DropDownItemButton onClick={() => setIsConfirmDeleteVisible(true)}>{t("Delete")}</DropDownItemButton>
            )}
            <ModalConfirm
                isVisible={isConfirmDeleteVisible}
                size={ModalSizes.MEDIUM}
                title={t("Delete Automation Rule")}
                onCancel={() => setIsConfirmDeleteVisible(false)}
                onConfirm={handleDelete}
                confirmTitle={t("Delete")}
                isConfirmLoading={isLoading}
                fullWidthContent
            >
                {error && <Message type="error" stringContents={error.message} icon={<ErrorIcon />} />}
                <Message
                    stringContents={t("This action cannot be undone")}
                    contents={<div className={messagesClasses().content}>{t("This action cannot be undone")}</div>}
                    icon={<Icon className={messagesClasses().icon} icon={"status-warning"} size={"compact"} />}
                />
                <p className={classes.padded(true)}>
                    <Translate
                        source={`Are you sure you want to delete the <0 /> automation rule?`}
                        c0={<strong>{props.name ?? `Automation Rule ${props.automationRuleID}`}</strong>}
                    />
                </p>
            </ModalConfirm>
        </>
    );
}
