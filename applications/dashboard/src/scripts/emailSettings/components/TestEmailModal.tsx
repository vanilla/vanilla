/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useRef } from "react";
import { JsonSchema, JsonSchemaForm, IJsonSchemaFormHandle, IFieldError } from "@vanilla/json-schema-forms";
import { t } from "@vanilla/i18n";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { useMutation } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { ITestEmailPayload, IEmailSettings } from "@dashboard/emailSettings/EmailSettings.types";
import { useToast } from "@library/features/toaster/ToastContext";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import Message from "@library/messages/Message";
import { IApiError } from "@library/@types/api/core";
import { useFormik } from "formik";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";

interface IProps {
    settings: IEmailSettings | {};
    onCancel(): void;
}

interface IFormValues {
    destinationAddress: string;
}

export default function TestEmailModal(props: IProps) {
    const { settings, onCancel } = props;
    const toast = useToast();

    const testEmailMutation = useMutation<any, IApiError, IFormValues>({
        mutationKey: ["sendTestEmail"],
        mutationFn: async (formValues: IFormValues) => {
            return await apiv2.post<ITestEmailPayload>("emails/send-test", {
                ...formValues,
                from: {
                    supportName: settings["outgoingEmails.supportName"],
                    supportAddress: settings["outgoingEmails.supportAddress"],
                },
                emailFormat: settings["emailStyles.format"] == true ? "html" : "text",
                templateStyles: {
                    logoUrl: settings["emailStyles.image"],
                    textColor: settings["emailStyles.textColor"],
                    backgroundColor: settings["emailStyles.backgroundColor"],
                    containerBackgroundColor: settings["emailStyles.containerBackgroundColor"],
                    buttonTextColor: settings["emailStyles.buttonTextColor"],
                    buttonBackgroundColor: settings["emailStyles.buttonBackgroundColor"],
                },
                footer: JSON.stringify(settings["outgoingEmails.footer"]),
            });
        },
        onSuccess: () => {
            onCancel();
            toast.addToast({
                dismissible: true,
                body: <>{t("The email has been sent.")}</>,
            });
        },
    });

    return (
        <TestEmailModalImpl
            onSubmit={testEmailMutation.mutateAsync}
            onCancel={onCancel}
            isLoading={testEmailMutation.isLoading}
            topLevelErrors={testEmailMutation.error}
        />
    );
}

export function TestEmailModalImpl(props: {
    onSubmit: (values: IFormValues) => Promise<void>;
    onCancel: () => void;
    isLoading: boolean;
    topLevelErrors: IApiError | null;
}) {
    const SCHEMA: JsonSchema = {
        type: "object",
        properties: {
            destinationAddress: {
                type: "string",
                pattern: "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$",
                errorMessage: t("Not a valid email"),
                "x-control": {
                    inputType: "textBox",
                    label: t("Recipient's Email Address"),
                    description: t("The email address this test will be sent to."),
                },
            },
        },
        required: ["destinationAddress"],
    };

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const { values, isSubmitting, setValues, submitForm, validateForm } = useFormik<Partial<IFormValues>>({
        initialValues: {
            destinationAddress: "",
        },
        onSubmit: async function (values) {
            try {
                await props.onSubmit(values as IFormValues);
            } catch (e) {
                if (e.errors) {
                    setFieldErrors(e.errors);
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

    return (
        <Modal
            isVisible={true}
            size={ModalSizes.LARGE}
            exitHandler={() => {
                props.onCancel();
            }}
        >
            <form
                role="form"
                onSubmit={async (e) => {
                    e.preventDefault();
                    await submitForm();
                }}
            >
                <Frame
                    header={
                        <FrameHeader
                            closeFrame={() => {
                                props.onCancel();
                            }}
                            title={t("Send a Test Email")}
                        />
                    }
                    body={
                        <FrameBody>
                            {props.topLevelErrors && (
                                <Message
                                    className={
                                        (accountSettingsClasses().topLevelErrors,
                                        dashboardAddEditUserClasses().topLevelError)
                                    }
                                    type={"error"}
                                    stringContents={props.topLevelErrors?.message}
                                />
                            )}

                            <JsonSchemaForm
                                disabled={isSubmitting}
                                fieldErrors={fieldErrors}
                                schema={SCHEMA}
                                instance={values}
                                FormControlGroup={DashboardFormControlGroup}
                                FormControl={DashboardFormControl}
                                onChange={setValues}
                                ref={schemaFormRef}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => {
                                    props.onCancel();
                                }}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                {props.isLoading ? <ButtonLoader /> : t("Send")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
