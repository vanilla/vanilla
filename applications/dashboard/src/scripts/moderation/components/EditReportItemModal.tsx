/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FormControlGroup, FormControlWithNewDropdown } from "@library/forms/FormControl";
import { IApiError, IFieldError } from "@library/@types/api/core";
import { IJsonSchemaFormHandle, JSONSchemaType, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useRef, useState } from "react";

import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { ErrorIcon } from "@library/icons/common";
import ErrorMessages from "@library/forms/ErrorMessages";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { MyValue } from "@library/vanilla-editor/typescript";
import { css } from "@emotion/css";
import { deserializeHtml } from "@library/vanilla-editor/deserializeHtml";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { t } from "@vanilla/i18n";
import { useFormik } from "formik";

const errorMessageSpacing = css({
    marginBlockEnd: 8,
});

// AIDEV-NOTE: Type guard to safely check if error is an API error
function isApiError(error: unknown): error is IApiError {
    return (
        error !== null &&
        typeof error === "object" &&
        "response" in error &&
        error.response !== null &&
        typeof error.response === "object"
    );
}

interface IEditReportItemModalProps {
    isVisible: boolean;
    onSubmit: (values: EditReportItemForm) => Promise<void>;
    onClose: () => void;
    report: IReport;
}

interface EditReportItemForm {
    name?: string;
    body?: MyValue | string;
}

export function EditReportItemModal(props: IEditReportItemModalProps) {
    const { report, isVisible, onClose, onSubmit } = props;

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [showConfirmDialog, setShowConfirmDialog] = useState(false);
    const [serverErrors, setServerErrors] = useState<IApiError | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    // Determine if this is a discussion (has title) or comment (no title)
    const isDiscussion = report.recordType === "discussion";

    const initialValues: EditReportItemForm = {
        ...(isDiscussion && { name: report.recordName ?? "" }),
        body: report.recordHtml ? JSON.stringify(deserializeHtml(report.recordHtml)) : EMPTY_RICH2_BODY,
    };

    const schema: JSONSchemaType<EditReportItemForm> = {
        type: "object",
        properties: {
            ...(isDiscussion && {
                name: {
                    type: "string",
                    default: "",
                    "x-control": {
                        label: t("Title"),
                        inputType: "textBox",
                    },
                },
            }),
            body: {
                type: ["string", "array"],
                default: EMPTY_RICH2_BODY,
                "x-control": {
                    label: t("Body"),
                    inputType: "richeditor",
                },
            },
        },
        required: isDiscussion ? ["name", "body"] : ["body"],
    };

    const { values, setValues, submitForm, isSubmitting, dirty, resetForm } = useFormik<EditReportItemForm>({
        initialValues,
        enableReinitialize: true,
        onSubmit: async (formValues) => {
            setServerErrors(null);
            setFieldErrors({});
            try {
                await onSubmit(formValues);
                resetForm();
                onClose();
            } catch (error) {
                // Handle API errors
                if (isApiError(error)) {
                    setServerErrors(error);
                    if (error.response?.data?.errors) {
                        setFieldErrors(error.response.data.errors);
                    }
                } else {
                    // Handle generic errors
                    setServerErrors({
                        message: error instanceof Error ? error.message : "An unexpected error occurred",
                    } as IApiError);
                }
            }
        },
        validate: () => {
            const result = schemaFormRef?.current?.validate();
            const mappedErrors = mapValidationErrorsToFormikErrors(result?.errors ?? []);
            return mappedErrors ?? {};
        },
        validateOnChange: false,
    });

    const handleClose = () => {
        if (dirty) {
            setShowConfirmDialog(true);
        } else {
            resetForm();
            setServerErrors(null);
            setFieldErrors({});
            onClose();
        }
    };

    const handleConfirmClose = () => {
        setShowConfirmDialog(false);
        resetForm();
        setServerErrors(null);
        setFieldErrors({});
        onClose();
    };

    return (
        <>
            <Modal isVisible={isVisible} exitHandler={handleClose} size={ModalSizes.MEDIUM}>
                <form
                    role="form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        void submitForm();
                    }}
                >
                    <Frame
                        header={<FrameHeader title={t("Edit & Approve Post")} closeFrame={handleClose} />}
                        body={
                            <FrameBody hasVerticalPadding>
                                {serverErrors && (
                                    <Message
                                        type="error"
                                        stringContents={serverErrors.message ?? "Validation Error"}
                                        icon={<ErrorIcon />}
                                        contents={<ErrorMessages errors={[serverErrors]} />}
                                        className={errorMessageSpacing}
                                    />
                                )}
                                <JsonSchemaForm
                                    ref={schemaFormRef}
                                    disabled={isSubmitting}
                                    fieldErrors={fieldErrors}
                                    schema={schema}
                                    instance={values}
                                    FormControlGroup={FormControlGroup}
                                    FormControl={FormControlWithNewDropdown}
                                    onChange={setValues}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button onClick={handleClose} buttonType={ButtonTypes.TEXT}>
                                    {t("Cancel")}
                                </Button>
                                <Button type="submit" buttonType={ButtonTypes.TEXT_PRIMARY} disabled={isSubmitting}>
                                    {isSubmitting ? <ButtonLoader /> : t("Save & Approve")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>

            <ModalConfirm
                isVisible={showConfirmDialog}
                title={t("Unsaved Changes")}
                onCancel={() => setShowConfirmDialog(false)}
                onConfirm={handleConfirmClose}
                confirmTitle={t("Exit")}
            >
                {t("Are you sure you want to discard your changes?")}
            </ModalConfirm>
        </>
    );
}
