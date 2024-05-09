/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, IServerError, LoadStatus } from "@library/@types/api/core";
import { useIntegrationContext } from "@library/features/discussions/integrations/Integrations.context";
import { IPostAttachmentParams } from "@library/features/discussions/integrations/Integrations.types";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { FormControl, FormControlGroup, FormControlWithNewDropdown } from "@library/forms/FormControl";
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
import { EMPTY_SCHEMA, IJsonSchemaFormHandle, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { getDefaultValuesFromSchema, mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { useFormik } from "formik";
import React, { useEffect, useRef, useState } from "react";
import Message from "@library/messages/Message";
import ErrorMessages from "@library/forms/ErrorMessages";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import Translate from "@library/content/Translate";

interface IIntegrationModalProps extends Pick<React.ComponentProps<typeof Modal>, "isVisible" | "exitHandler"> {
    onSuccess?: () => Promise<void>;
}

export default function IntegrationModalLoadable(props: IIntegrationModalProps) {
    const { isVisible, exitHandler, onSuccess } = props;

    const toast = useToast();

    const [serverError, setServerError] = useState<IServerError | null>(null);
    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);

    const {
        name,
        label,
        submitButton,
        schema: { status: schemaStatus, data: integrationSchema, error: schemaError },
        getSchema,
        postAttachment,
        CustomIntegrationForm,
        beforeSubmit,
        ...rest
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
            body: <Translate source="Success! Submitted to <0/>." c0={name} />,
        });
    }

    async function handleError(e: IApiError) {
        setServerError(e);
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
            setServerError(null);
            try {
                const finalValues = beforeSubmit?.(values) ?? values;
                await postAttachment(finalValues);
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
                                    {serverError && (
                                        <Message
                                            error={serverError}
                                            stringContents={serverError.message}
                                            className={classesFrameBody.error}
                                        />
                                    )}
                                    {schemaReady && Object.keys(values).length > 0 ? (
                                        <>
                                            {CustomIntegrationForm ? (
                                                <CustomIntegrationForm
                                                    values={values}
                                                    schema={integrationSchema}
                                                    onChange={setValues}
                                                    fieldErrors={serverError?.errors}
                                                />
                                            ) : (
                                                <JsonSchemaForm
                                                    ref={schemaFormRef}
                                                    schema={integrationSchema}
                                                    instance={values}
                                                    onChange={setValues}
                                                    FormControl={FormControlWithNewDropdown}
                                                    FormControlGroup={FormControlGroup}
                                                    fieldErrors={serverError?.errors}
                                                />
                                            )}
                                        </>
                                    ) : schemaError ? (
                                        <CoreErrorMessages error={schemaError} />
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
                                    {isSubmitting ? <ButtonLoader /> : t(submitButton) ?? t("Save")}
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
