/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useRef, useMemo } from "react";
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
import { CommunityMemberInput } from "@vanilla/addon-vanilla/forms/CommunityMemberInput";
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
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { getEmailSettingsSchemas } from "@dashboard/emailSettings/EmailSettings.utils";
import { useConfigMutation, useConfigQuery } from "@library/config/configHooks";

interface IProps {
    onCancel(): void;
}

interface IFormValues {
    destinationUserID: number;
    destinationAddress: string;
}

type ITestDigestPayload = ITestEmailPayload | IFormValues;

export default function TestDigestModal(props: IProps) {
    const { onCancel } = props;
    const toast = useToast();

    const emailSettingsSchema = getEmailSettingsSchemas().emailSettingsSchema;

    const { data } = useConfigQuery(
        Object.keys({
            ...emailSettingsSchema["properties"],
        }),
    );

    const configs = data ?? {};

    const emailSettings = useMemo(() => {
        const refinedEmailSettings = {
            // initial values
            ...Object.keys(emailSettingsSchema.properties).reduce((acc, currentKey) => {
                const value = emailSettingsSchema.properties[currentKey];
                return {
                    ...acc,
                    [currentKey]: value.type === "boolean" ? false : value.type === "number" ? 1 : "",
                };
            }, {}),
            // final values with actual data
            ...(configs &&
                Object.fromEntries(
                    Object.keys(emailSettingsSchema.properties).map((key) => {
                        if (key === "emailNotifications.disabled") {
                            return [key, !configs[key]];
                        } else if (key === "emailStyles.format") {
                            return [key, configs[key] === "html" ? true : false];
                        }
                        return [key, configs[key] ?? ""];
                    }),
                )),
        };

        return refinedEmailSettings;
    }, [emailSettingsSchema.properties, configs]);

    const testEmailMutation = useMutation<any, IApiError, IFormValues>({
        mutationKey: ["sendTestEmail"],
        mutationFn: async (formValues: IFormValues) => {
            return await apiv2.post<ITestDigestPayload>("emails/send-test-digest", {
                ...formValues,
                deliveryDate: new Date(),
                from: {
                    supportName: emailSettings["outgoingEmails.supportName"],
                    supportAddress: emailSettings["outgoingEmails.supportAddress"],
                },
                emailFormat: emailSettings["emailStyles.format"] == true ? "html" : "text",
                templateStyles: {
                    logoUrl: emailSettings["emailStyles.image"],
                    textColor: emailSettings["emailStyles.textColor"],
                    backgroundColor: emailSettings["emailStyles.backgroundColor"],
                    containerBackgroundColor: emailSettings["emailStyles.containerBackgroundColor"],
                    buttonTextColor: emailSettings["emailStyles.buttonTextColor"],
                    buttonBackgroundColor: emailSettings["emailStyles.buttonBackgroundColor"],
                },
                footer: isJsonString(emailSettings["outgoingEmails.footer"])
                    ? emailSettings["outgoingEmails.footer"]
                    : JSON.stringify(emailSettings["outgoingEmails.footer"]),
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
        <TestDigestModalImpl
            onSubmit={testEmailMutation.mutateAsync}
            onCancel={onCancel}
            isLoading={testEmailMutation.isLoading}
            topLevelErrors={testEmailMutation.error}
        />
    );
}

function CustomCommunityMemberInput(
    props: Omit<React.ComponentProps<typeof CommunityMemberInput>, "onChange"> & {
        onChange: (value: number) => void;
    },
) {
    const { onChange, ...rest } = props;
    return (
        <CommunityMemberInput
            {...rest}
            onChange={(tokens) => {
                onChange(tokens[0].data.userID);
            }}
        />
    );
}

function isJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

export function TestDigestModalImpl(props: {
    onSubmit: (values: IFormValues) => Promise<void>;
    onCancel: () => void;
    isLoading: boolean;
    topLevelErrors: IApiError | null;
}) {
    const SCHEMA: JsonSchema = {
        type: "object",
        properties: {
            destinationUserID: {
                type: "number",
                errorMessage: t("Not a valid user ID"),
                "x-control": {
                    inputType: "custom",
                    component: CustomCommunityMemberInput,
                    componentProps: {
                        placeholder: t("Start typing username"),
                    },
                    label: t("Community Member Content"),
                    description: t(
                        "The test digest will generate content as if it were this user receiving the digest.",
                    ),
                },
                minimum: 1,
            },
            destinationAddress: {
                type: "string",
                pattern: "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$",
                errorMessage: t("Not a valid email"),
                "x-control": {
                    inputType: "textBox",
                    label: t("Recipient"),
                    description: t("The email address this test will be sent to."),
                },
            },
        },
        required: ["destinationUserID", "destinationAddress"],
    };

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const { values, isSubmitting, setValues, submitForm, validateForm } = useFormik<Partial<IFormValues>>({
        initialValues: {
            destinationUserID: 0,
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
                            title={t("Send Test Email Digest")}
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

                            <DashboardSchemaForm
                                disabled={isSubmitting}
                                fieldErrors={fieldErrors}
                                schema={SCHEMA}
                                instance={values}
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
