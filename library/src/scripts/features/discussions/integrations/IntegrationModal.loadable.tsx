/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { useIntegrationContext } from "@library/features/discussions/integrations/Integrations.context";
import { IPostAttachmentParams } from "@library/features/discussions/integrations/Integrations.types";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Loader from "@library/loaders/Loader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { EMPTY_SCHEMA, IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { useFormik } from "formik";
import React, { useEffect, useRef, useState } from "react";

interface IIntegrationModalProps extends Pick<React.ComponentProps<typeof Modal>, "isVisible" | "exitHandler"> {
    onSuccess?: () => Promise<void>;
}

// This only works for simple schemas, not nested ones
// TODO: improve this for nested schemas
export function getDefaultValuesFromSchema(schema: JsonSchema): any {
    const defaultValues: any = {};

    for (const [key, value] of Object.entries(schema.properties ?? {})) {
        if ("default" in value) {
            defaultValues[key] = value.default;
        }
    }

    return defaultValues;
}

export default function IntegrationModalLoadable(props: IIntegrationModalProps) {
    const { isVisible, exitHandler, onSuccess } = props;

    const toast = useToast();

    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);

    const {
        label,
        submitButton,
        schema: { status: schemaStatus, data: integrationSchema },
        getSchema,
        postAttachment,
    } = useIntegrationContext();

    useEffect(() => {
        // request the schema when the modal is opened
        if (isVisible && schemaStatus === LoadStatus.PENDING) {
            getSchema();
        }
    }, [isVisible]);

    const schemaReady = ![LoadStatus.PENDING, LoadStatus.LOADING].includes(schemaStatus) && !!integrationSchema;

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);

    async function handleSuccess() {
        await onSuccess?.();
        toast.addToast({
            autoDismiss: true,
            body: t("Integration successful"), //FIXME: copy
        });
    }

    async function handleError(e: any) {
        toast.addToast({
            autoDismiss: true,
            body: t("Integration failed"), //FIXME: copy
        });
    }

    function handleClose() {
        setConfirmDialogVisible(false);
        exitHandler?.();
        resetForm();
    }

    const { values, submitForm, setValues, isSubmitting, resetForm, dirty } = useFormik<IPostAttachmentParams>({
        initialValues: {
            ...{},
            ...getDefaultValuesFromSchema(integrationSchema ?? EMPTY_SCHEMA),
        },
        onSubmit: async (values) => {
            try {
                await postAttachment(values);
                handleClose();
                await handleSuccess();
            } catch (e) {
                await handleError(e);
            }
        },
        enableReinitialize: true,
        validateOnChange: false,
        validateOnMount: false,
        validate: () => {
            const result = schemaFormRef?.current?.validate();
            const mappedErrors = mapValidationErrorsToFormikErrors(result?.errors ?? []);

            return mappedErrors ?? {};
        },
    });

    function handleCloseButton() {
        if (schemaReady && dirty) {
            // this is to prevent the escape key handler from being called in both the confirm dialog and the modal
            setTimeout(() => {
                setConfirmDialogVisible(true);
            }, 1);
        } else {
            handleClose();
        }
    }

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    return (
        <>
            <Modal isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={handleCloseButton}>
                <ConditionalWrap
                    tag={"form"}
                    componentProps={{
                        role: "form",
                        onSubmit: async (e) => {
                            e.preventDefault();
                            await submitForm();
                        },
                    }}
                    condition={schemaReady}
                >
                    <Frame
                        header={<FrameHeader closeFrame={handleCloseButton} title={t(label)} />}
                        body={
                            <FrameBody>
                                <div className={classesFrameBody.contents}>
                                    {schemaReady && Object.keys(values).length > 0 ? (
                                        <JsonSchemaForm
                                            ref={schemaFormRef}
                                            schema={integrationSchema}
                                            instance={values}
                                            onChange={setValues}
                                            FormControl={FormControl}
                                            FormControlGroup={FormControlGroup}
                                        />
                                    ) : (
                                        <Loader small />
                                    )}
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={handleCloseButton}
                                    className={classFrameFooter.actionButton}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    submit={schemaReady}
                                    disabled={!schemaReady || isSubmitting}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    className={classFrameFooter.actionButton}
                                >
                                    {!schemaReady || isSubmitting ? <ButtonLoader /> : t(submitButton) ?? t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </ConditionalWrap>
            </Modal>

            <ModalConfirm
                isVisible={confirmDialogVisible}
                title={t("Unsaved Changes")}
                onCancel={() => setConfirmDialogVisible(false)}
                onConfirm={handleClose}
                confirmTitle={t("Exit")}
            >
                {t("You are leaving without saving your changes. Are you sure you want to exit without submitting?")}
            </ModalConfirm>
        </>
    );
}
