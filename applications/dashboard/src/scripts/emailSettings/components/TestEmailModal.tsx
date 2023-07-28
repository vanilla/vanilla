/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useMutation } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { ITestEmailPayload, IEmailSettings } from "@dashboard/emailSettings/types";
import { useToast } from "@library/features/toaster/ToastContext";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import Message from "@library/messages/Message";
import { IApiError } from "@library/@types/api/core";

interface IProps {
    settings: IEmailSettings | {};
    onCancel(): void;
}

export default function TestEmailModal(props: IProps) {
    const { settings, onCancel } = props;
    const [destinationAddress, setDestinationAddress] = useState<string>("");
    const toast = useToast();

    const testEmailMutation = useMutation<any, IApiError>({
        mutationKey: ["sendTestEmail"],
        mutationFn: async () => {
            return await apiv2.post<ITestEmailPayload>("emails/send-test", {
                destinationAddress,
                from: {
                    supportName: settings["outgoingEmails.supportName"],
                    supportAddress: settings["outgoingEmails.supportAddress"],
                },
                emailFormat: settings["emailStyles.format"] == true ? "html" : "text",
                templateStyles: {
                    logoUrl: settings["emailStyles.logoUrl"],
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
        <>
            <Modal
                isVisible={true}
                size={ModalSizes.LARGE}
                exitHandler={() => {
                    onCancel();
                }}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        testEmailMutation.mutate();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => {
                                    onCancel();
                                }}
                                title={t("Send a Test Email")}
                            />
                        }
                        body={
                            <FrameBody>
                                {testEmailMutation.error && (
                                    <Message
                                        className={
                                            (accountSettingsClasses().topLevelErrors,
                                            dashboardAddEditUserClasses().topLevelError)
                                        }
                                        type={"error"}
                                        stringContents={testEmailMutation.error.message}
                                    />
                                )}
                                <DashboardFormGroup label={t("Recipient's Email Address")}>
                                    <DashboardInput
                                        inputProps={{
                                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                setDestinationAddress(event.target.value);
                                            },
                                        }}
                                        errors={testEmailMutation.error?.errors?.destinationAddress}
                                    />
                                </DashboardFormGroup>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        onCancel();
                                    }}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {t("Send")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </>
    );
}
