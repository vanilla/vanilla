/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { AutomationRuleFormValues, IAutomationRule } from "@dashboard/automationRules/AutomationRules.types";
import ModalSizes from "@library/modal/ModalSizes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IError } from "@library/errorPages/CoreErrorMessages";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Modal from "@library/modal/Modal";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { cx } from "@emotion/css";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { AutomationRulesPreviewRenderer } from "@dashboard/automationRules/preview/AutomationRulesPreviewRenderer";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import ErrorMessages from "@library/forms/ErrorMessages";
import { JsonSchema } from "packages/vanilla-json-schema-forms/src";

interface IProps {
    formValues: AutomationRuleFormValues;
    onConfirm?: (newStatus?: IAutomationRule["status"]) => void;
    onCancel?: () => void;
    isRuleRunning?: boolean;
    isVisible?: boolean;
    fromStatusToggle?: boolean;
    schema?: JsonSchema;
}

export function AutomationRulesPreviewModal(props: IProps) {
    const { formValues, schema } = props;
    const classes = automationRulesClasses();
    const [isModalVisible, setIsModalVisible] = useState(Boolean(props.isVisible));
    const [error, setError] = useState<IError>();
    const [loadedPreviewContentIsEmpty, setLoadedPreviewContentIsEmpty] = useState<boolean>(false);

    useEffect(() => {
        if (props.isVisible) setIsModalVisible(props.isVisible);
    }, [props.isVisible]);

    const onClose = async () => {
        props.onCancel?.();
        setIsModalVisible(false);
    };

    const onPreviewConfirm = async () => {
        try {
            await props.onConfirm?.("active");
            setIsModalVisible(false);
        } catch (error) {
            setError({
                message: error.message,
            });
        }
    };

    const modalFooterButtons = !props.onConfirm ? (
        <Button buttonType={ButtonTypes.TEXT} onClick={onClose}>
            {t("Close")}
        </Button>
    ) : (
        <>
            <Button buttonType={ButtonTypes.TEXT} onClick={onClose}>
                {t("Cancel")}
            </Button>
            <Button
                buttonType={ButtonTypes.TEXT}
                onClick={onPreviewConfirm}
                disabled={!props.fromStatusToggle && loadedPreviewContentIsEmpty}
            >
                {t("Confirm")}
            </Button>
        </>
    );

    return (
        <>
            {!props.onConfirm && (
                <DropDownItemButton onClick={() => setIsModalVisible(true)}>{t("Preview")}</DropDownItemButton>
            )}
            <Modal
                isVisible={isModalVisible}
                exitHandler={onClose}
                size={ModalSizes.MEDIUM}
                titleID={"automation-rules-preview-modal"}
                isFixHeight
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={"automation-rules-preview-modal"}
                            closeFrame={onClose}
                            title={t("Automation Rule Preview")}
                        />
                    }
                    body={
                        <FrameBody>
                            {error && (
                                <div className={classes.padded(true)}>
                                    <Message
                                        type="error"
                                        stringContents={error.message}
                                        icon={<ErrorIcon />}
                                        contents={<ErrorMessages errors={[error]} />}
                                    />
                                </div>
                            )}
                            {(!error || !!Object.keys(error).length) && (
                                <div className={cx(frameBodyClasses().contents)}>
                                    <AutomationRulesPreviewRenderer
                                        formValues={formValues}
                                        fromStatusToggle={props.fromStatusToggle}
                                        onPreviewContentLoad={setLoadedPreviewContentIsEmpty}
                                        schema={schema}
                                    />
                                </div>
                            )}
                        </FrameBody>
                    }
                    footer={<FrameFooter justifyRight>{modalFooterButtons}</FrameFooter>}
                />
            </Modal>
        </>
    );
}
